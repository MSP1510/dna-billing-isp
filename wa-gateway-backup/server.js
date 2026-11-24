const fs = require('fs');
const express = require('express');
const { default: makeWASocket, useMultiFileAuthState } = require('@whiskeysockets/baileys');
const QRCode = require('qrcode');
const pino = require('pino');

const app = express();
app.use(express.json());
let sock, waStatus = 'Menunggu QR...';

function saveState(st, qr = '') {
    waStatus = st;
    fs.writeFileSync('/tmp/dna_wa.json', JSON.stringify({ status: st, qr: qr }));
}
saveState('Memulai WhatsApp...');

async function startWA() {
    const { state, saveCreds } = await useMultiFileAuthState('/opt/dna-wa-gateway/auth_session');
    sock = makeWASocket({ auth: state, logger: pino({ level: 'silent' }), browser: ['DNA_Billing', 'Chrome', '1.0'] });
    sock.ev.on('creds.update', saveCreds);
    sock.ev.on('connection.update', async (u) => {
        if (u.qr) { saveState('Scan QR Code', await QRCode.toDataURL(u.qr)); }
        if (u.connection === 'open') { saveState('TERHUBUNG', ''); }
        if (u.connection === 'close') { saveState('Menghubungkan ulang...', ''); setTimeout(startWA, 3000); }
    });
}
startWA();

app.post('/send-message', async (req, res) => {
    const { api_key, number, message, image_url } = req.body;
    if (api_key !== 'dna-secret-key') return res.status(403).json({ status: false, msg: 'API Key salah' });
    if (waStatus !== 'TERHUBUNG') return res.json({ status: false, msg: 'WA belum di-scan' });
    try {
        const jid = number + '@s.whatsapp.net';
        if (image_url && image_url.includes('/uploads/')) { const lp = '/var/www/html/uploads/' + image_url.split('/uploads/'); await sock.sendMessage(jid, { image: fs.readFileSync(lp), caption: message }); } else if (image_url && image_url.startsWith('http')) { await sock.sendMessage(jid, { image: { url: image_url }, caption: message }); }
        else await sock.sendMessage(jid, { text: message });
        res.json({ status: true, msg: 'Terkirim ke ' + number });
    } catch (e) { res.json({ status: false, msg: e.message }); }
});
app.listen(3001, '127.0.0.1', () => console.log('WA_READY'));
