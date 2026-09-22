const crypto = require('crypto');

function signBody(body, secret) {
  return crypto.createHmac('sha256', secret).update(body).digest('hex');
}

module.exports = { signBody };
