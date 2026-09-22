const express = require('express');
const { getGroups, getGroupHistory } = require('../services/whatsappService');

const router = express.Router();

router.get('/api/groups', async (req, res, next) => {
  try {
    res.json({ ok: true, groups: await getGroups() });
  } catch (error) {
    next(error);
  }
});

router.get('/api/groups/:groupId/history', async (req, res, next) => {
  try {
    const limit = Number(req.query.limit || 0);
    const since = req.query.since ? Number(req.query.since) : null;
    const messages = await getGroupHistory(req.params.groupId, { limit, sinceTimestamp: since });

    res.json({
      ok: true,
      group_id: req.params.groupId,
      count: messages.length,
      oldest: messages.length ? messages[0].timestamp : null,
      newest: messages.length ? messages[messages.length - 1].timestamp : null,
      messages,
    });
  } catch (error) {
    next(error);
  }
});

module.exports = router;
