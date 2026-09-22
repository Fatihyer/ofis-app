const express = require('express');
const { getStatus, getQr } = require('../services/whatsappService');

const router = express.Router();

router.get('/health', async (req, res) => {
  res.json(await getStatus());
});

router.get('/api/qr', async (req, res) => {
  const qr = getQr();
  if (!qr) return res.status(404).json({ ok: false, message: 'QR not available' });
  res.json({ ok: true, qr });
});

module.exports = router;
