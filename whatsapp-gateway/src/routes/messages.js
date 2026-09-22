const express = require('express');
const { sendGroupMessage } = require('../services/whatsappService');

const router = express.Router();

router.post('/api/groups/send', async (req, res, next) => {
  try {
    const { group_id: groupId, message } = req.body || {};
    if (!groupId || !message) return res.status(422).json({ ok: false, error: 'group_id and message are required' });
    if (!String(groupId).endsWith('@g.us')) return res.status(422).json({ ok: false, error: 'Only group messages are allowed' });

    res.json(await sendGroupMessage(groupId, message));
  } catch (error) {
    next(error);
  }
});

module.exports = router;
