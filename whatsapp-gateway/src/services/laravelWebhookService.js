const axios = require('axios');
const { signBody } = require('../utils/signature');

async function sendGroupMessageWebhook(payload) {
  const url = process.env.LARAVEL_WEBHOOK_URL;
  const secret = process.env.LARAVEL_WEBHOOK_SECRET;

  if (!url || !secret) {
    throw new Error('Laravel webhook URL/secret missing');
  }

  const body = JSON.stringify(payload);
  const signature = signBody(body, secret);

  await axios.post(url, body, {
    headers: {
      'Content-Type': 'application/json',
      'X-WhatsApp-Signature': signature,
    },
    timeout: 15000,
  });
}

module.exports = { sendGroupMessageWebhook };
