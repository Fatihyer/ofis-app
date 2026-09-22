require('dotenv').config();

const express = require('express');
const cors = require('cors');
const statusRoutes = require('./routes/status');
const groupsRoutes = require('./routes/groups');
const messagesRoutes = require('./routes/messages');
const { startWhatsApp, stopWhatsApp } = require('./services/whatsappService');

const app = express();
const port = Number(process.env.PORT || 3001);

app.use(cors());
app.use(express.json({ limit: '1mb' }));

app.use((req, res, next) => {
  if (req.path === '/health') return next();

  const expected = process.env.GATEWAY_API_TOKEN;
  const authorization = req.header('Authorization') || '';
  const token = authorization.startsWith('Bearer ') ? authorization.slice(7) : '';

  if (!expected || token !== expected) {
    return res.status(401).json({ ok: false, error: 'Unauthorized' });
  }

  next();
});

app.use(statusRoutes);
app.use(groupsRoutes);
app.use(messagesRoutes);

app.use((error, req, res, next) => {
  console.error(error.message);
  res.status(500).json({ ok: false, error: error.message });
});

let shuttingDown = false;

async function shutdown(signal) {
  if (shuttingDown) return;
  shuttingDown = true;
  console.log(`Received ${signal}, closing WhatsApp session...`);

  try {
    await stopWhatsApp();
    console.log('WhatsApp session closed cleanly.');
  } catch (error) {
    console.error('Shutdown error:', error.message);
  }

  process.exit(0);
}

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));

app.listen(port, async () => {
  console.log(`WhatsApp gateway listening on ${port}`);
  try {
    await startWhatsApp();
  } catch (error) {
    console.error('Unable to start WhatsApp:', error.message);
  }
});
