const wppconnect = require('@wppconnect-team/wppconnect');
const qrcode = require('qrcode');
const fs = require('fs');
const { sendGroupMessageWebhook } = require('./laravelWebhookService');

let client = null;
let status = 'starting';
let lastQr = null;
let startedAt = Date.now();
let groupsCache = [];
let groupsCacheUpdatedAt = null;
let groupsRefreshPromise = null;

async function startWhatsApp() {
  if (client) return client;

  const session = process.env.WHATSAPP_SESSION_NAME || 'main';

  client = await wppconnect.create({
    session,
    folderNameToken: './sessions',
    headless: true,
    useChrome: true,
    autoClose: 0,
    disableWelcome: true,
    browserArgs: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
    puppeteerOptions: {
      executablePath: executablePath(),
    },
    catchQR: async (base64Qr, asciiQR, attempts, urlCode) => {
      status = 'qr';
      lastQr = {
        attempts,
        urlCode: urlCode || null,
        ascii: asciiQR,
        base64: base64Qr,
        dataUrl: await qrcode.toDataURL(urlCode || base64Qr),
      };
    },
    statusFind: (state) => {
      status = state || status;
      if (isLoggedState(state)) {
        lastQr = null;
      }
    },
  });

  status = 'connected';
  lastQr = null;
  refreshGroupsCache().catch((error) => {
    console.error('Initial group cache refresh failed:', error.message);
  });
  setInterval(() => {
    refreshGroupsCache().catch((error) => {
      console.error('Scheduled group cache refresh failed:', error.message);
    });
  }, Number(process.env.GROUPS_REFRESH_INTERVAL_MS || 300000));

  client.onMessage(async (message) => {
    try {
      const chatId = normalizeId(message.chatId || message.from || message.to);

      if (!chatId.endsWith('@g.us')) {
        return;
      }

      const normalized = await normalizeMessage(message, chatId);

      await sendGroupMessageWebhook({
        event: 'group.message.received',
        session,
        message: normalized,
      });
    } catch (error) {
      console.error('Group message webhook failed:', error.message);
    }
  });

  return client;
}

function isLoggedState(state) {
  const value = String(state || '').toUpperCase();
  return ['CONNECTED', 'INCHAT', 'ISLOGGED', 'QRREADSUCCESS', 'SYNCING', 'PAIRING'].some((flag) => value.includes(flag));
}

function executablePath() {
  const configured = process.env.PUPPETEER_EXECUTABLE_PATH;
  if (configured && fs.existsSync(configured)) return configured;

  const localBrowserRoot = `${process.cwd()}/browsers/chrome`;
  if (fs.existsSync(localBrowserRoot)) {
    for (const build of fs.readdirSync(localBrowserRoot)) {
      const chrome = `${localBrowserRoot}/${build}/chrome-linux64/chrome`;
      if (fs.existsSync(chrome)) return chrome;
    }
  }

  return undefined;
}

async function normalizeMessage(message, chatId) {
  const groupName = await groupNameFor(chatId, message);
  const senderId = normalizeId(message.author || message.sender?.id || message.from);
  const senderPhone = phoneFromId(senderId);

  return {
    external_id: String(message.id || message.id?._serialized || ''),
    group_id: chatId,
    group_name: groupName,
    sender_id: senderId,
    sender_phone: senderPhone,
    sender_name: displayName(message.notifyName || message.sender?.pushname || message.sender?.name),
    is_from_me: Boolean(message.fromMe),
    message_type: normalizeMessageType(message.type),
    body: message.body || message.caption || '',
    timestamp: Number(message.t || message.timestamp || Math.floor(Date.now() / 1000)),
  };
}

async function groupNameFor(chatId, message) {
  const messageName = displayName(message.chat?.name || message.chat?.formattedTitle);
  if (messageName) return messageName;

  try {
    const chat = await client.getChatById(chatId);
    return displayName(chat?.name || chat?.formattedTitle) || 'Groupe WhatsApp';
  } catch (error) {
    return 'Groupe WhatsApp';
  }
}

function normalizeMessageType(type) {
  if (!type || type === 'chat') return 'text';
  return String(type);
}

function normalizeId(value) {
  if (!value) return '';
  if (typeof value === 'string') return value;
  if (value._serialized) return value._serialized;
  if (value.user && value.server) return `${value.user}@${value.server}`;
  return String(value);
}

function phoneFromId(value) {
  const id = normalizeId(value);
  if (!id || id.endsWith('@lid') || id.endsWith('@g.us')) return null;

  const phone = id.replace('@c.us', '').replace(/\D/g, '');
  return phone.length >= 6 ? phone : null;
}

function displayName(value) {
  const name = String(value || '').trim();
  if (!name || /@(?:lid|g\.us|c\.us)$/i.test(name)) return null;
  if (/^\d{8,}$/.test(name)) return null;
  return name;
}

async function getStatus() {
  return {
    status: 'ok',
    whatsapp: client ? status : 'not_started',
    session: process.env.WHATSAPP_SESSION_NAME || 'main',
    uptime: Math.floor((Date.now() - startedAt) / 1000),
    qr_available: Boolean(lastQr),
    groups_cached: groupsCache.length,
    groups_cache_updated_at: groupsCacheUpdatedAt,
  };
}

function getQr() {
  return lastQr;
}

async function getGroups() {
  if (!client) throw new Error('WhatsApp is not started');

  if (groupsCache.length === 0) {
    try {
      await withTimeout(refreshGroupsCache(), Number(process.env.GROUPS_WARMUP_TIMEOUT_MS || 5000), 'Group cache warmup timed out');
    } catch (error) {
      console.error('On-demand group cache refresh failed:', error.message);
    }
  } else if (!groupsRefreshPromise) {
    refreshGroupsCache().catch((error) => {
      console.error('Background group cache refresh failed:', error.message);
    });
  }

  return groupsCache;
}

async function refreshGroupsCache() {
  if (!client) throw new Error('WhatsApp is not started');
  if (groupsRefreshPromise) return groupsRefreshPromise;

  groupsRefreshPromise = (async () => {
    const timeout = Number(process.env.GROUPS_REQUEST_TIMEOUT_MS || 12000);
    const groups = await withTimeout(loadGroupsFromWhatsApp(), timeout, 'WhatsApp group list timed out');
    groupsCache = normalizeGroups(groups);
    groupsCacheUpdatedAt = new Date().toISOString();
    return groupsCache;
  })();

  try {
    return await groupsRefreshPromise;
  } finally {
    groupsRefreshPromise = null;
  }
}

async function loadGroupsFromWhatsApp() {
  if (typeof client.getAllGroups === 'function') {
    return await client.getAllGroups();
  }

  if (typeof client.getAllChats === 'function') {
    const chats = await client.getAllChats();
    return (chats || []).filter((chat) => normalizeId(chat.id || chat.contact?.id || chat.wid).endsWith('@g.us'));
  }

  return [];
}

function normalizeGroups(groups) {
  return (groups || [])
    .filter(Boolean)
    .map((group) => {
      const id = normalizeId(group.id || group.contact?.id || group.wid);
      const name = displayName(group.name || group.formattedTitle || group.contact?.name);
      return {
        external_id: id,
        name: name || 'Groupe WhatsApp',
        participants_count: Array.isArray(group.groupMetadata?.participants)
          ? group.groupMetadata.participants.length
          : group.participantsCount || null,
      };
    })
    .filter((group) => group.external_id.endsWith('@g.us'));
}

function withTimeout(promise, timeout, message) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error(message)), timeout);
    promise
      .then((value) => {
        clearTimeout(timer);
        resolve(value);
      })
      .catch((error) => {
        clearTimeout(timer);
        reject(error);
      });
  });
}

async function sendGroupMessage(groupId, message) {
  if (!client) throw new Error('WhatsApp is not started');
  if (!String(groupId).endsWith('@g.us')) throw new Error('Only group ids are allowed');

  const result = await client.sendText(groupId, message);
  return {
    ok: true,
    message_id: String(result?.id || result?._serialized || `sent_${Date.now()}`),
    result,
  };
}

async function getGroupHistory(groupId, { limit = 0, sinceTimestamp = null } = {}) {
  if (!client) throw new Error('WhatsApp is not started');
  if (!String(groupId).endsWith('@g.us')) throw new Error('Only group ids are allowed');

  let messages = [];

  if (typeof client.getMessages === 'function') {
    try {
      messages = await client.getMessages(groupId, {
        count: limit > 0 ? limit : -1,
        direction: 'before',
      });
    } catch (error) {
      console.error(`getMessages failed for ${groupId}: ${error.message}`);
    }
  }

  if (!messages || messages.length === 0) {
    messages = await client.loadAndGetAllMessagesInChat(groupId, true, false);
  }

  let rows = (messages || []).map((message) => ({
    external_id: String(message.id?._serialized || message.id || ''),
    group_id: groupId,
    sender_id: normalizeId(message.author || message.sender?.id || message.from),
    sender_phone: phoneFromId(message.author || message.sender?.id || message.from),
    sender_name: displayName(message.notifyName || message.sender?.pushname || message.sender?.name),
    is_from_me: Boolean(message.fromMe),
    message_type: normalizeMessageType(message.type),
    body: message.body || message.caption || '',
    timestamp: Number(message.t || message.timestamp || 0),
  }));

  if (sinceTimestamp) {
    rows = rows.filter((row) => row.timestamp >= Number(sinceTimestamp));
  }

  rows.sort((a, b) => a.timestamp - b.timestamp);

  if (limit > 0 && rows.length > limit) {
    rows = rows.slice(-limit);
  }

  return rows;
}

async function stopWhatsApp() {
  if (!client) return;

  const current = client;
  client = null;
  status = 'stopping';

  try {
    await withTimeout(current.close(), 15000, 'WhatsApp close timed out');
  } catch (error) {
    console.error('WhatsApp close failed:', error.message);
  }
}

module.exports = {
  startWhatsApp,
  getGroupHistory,
  stopWhatsApp,
  getStatus,
  getQr,
  getGroups,
  refreshGroupsCache,
  sendGroupMessage,
};
