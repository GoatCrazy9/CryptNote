// --- References to DOM elements ---
const txt = document.getElementById('content');     // Textarea for user input
const expSel = document.getElementById('expire');   // Dropdown for expiration time
const btnCreate = document.getElementById('btnCreate'); // Button to create a paste
const btnClear = document.getElementById('btnClear');   // Button to clear textarea
const share = document.getElementById('share');         // Share container (hidden/shown after paste creation)
const sharelink = document.getElementById('sharelink'); // Input field with generated share link
const btnCopy = document.getElementById('btnCopy');     // Button to copy the link
const viewer = document.getElementById('viewer');       // Viewer container for decrypted paste
const decrypted = document.getElementById('decrypted'); // Textarea for displaying decrypted content


// --- Helper functions ---

/**
 * Convert an ArrayBuffer into a Base64-URL-safe string
 */
function toBase64Url(buf) {
  return btoa(String.fromCharCode(...new Uint8Array(buf)))
    .replace(/\+/g, '-') // Replace "+" with "-"
    .replace(/\//g, '_') // Replace "/" with "_"
    .replace(/=+$/, ''); // Remove padding "="
}

/**
 * Convert a Base64-URL-safe string back to an ArrayBuffer
 */
function fromBase64Url(str) {
  str = str.replace(/-/g, '+').replace(/_/g, '/');
  const pad = str.length % 4 ? 4 - (str.length % 4) : 0;
  if (pad) str += '='.repeat(pad);
  const bin = atob(str);
  return new Uint8Array([...bin].map(c => c.charCodeAt(0))).buffer;
}


/**
 * Encrypt plain text using AES-GCM (256-bit key).
 * Returns ciphertext, IV, and raw key.
 */
async function encryptText(plain) {
  const enc = new TextEncoder().encode(plain);
  const key = await crypto.subtle.generateKey(
    { name: 'AES-GCM', length: 256 },
    true,
    ['encrypt', 'decrypt']
  );
  const rawKey = await crypto.subtle.exportKey('raw', key);
  const iv = crypto.getRandomValues(new Uint8Array(12)); // 96-bit IV
  const ciphertext = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, key, enc);
  return { ciphertext, iv, rawKey };
}

/**
 * Decrypt AES-GCM ciphertext using provided Base64-URL encoded key and IV.
 */
async function decryptText(ciphertextB64, ivB64, rawKeyB64) {
  const ciphertext = fromBase64Url(ciphertextB64);
  const iv = new Uint8Array(fromBase64Url(ivB64));
  const rawKey = await crypto.subtle.importKey(
    'raw',
    fromBase64Url(rawKeyB64),
    { name: 'AES-GCM' },
    false,
    ['decrypt']
  );
  const plainBuf = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, rawKey, ciphertext);
  return new TextDecoder().decode(plainBuf);
}


// --- Create Paste ---
btnCreate.addEventListener('click', async () => {
  const content = txt.value.trim();
  if (!content) { alert('Please write something first.'); return; }

  btnCreate.disabled = true; // Prevent double-clicks
  try {
    // Encrypt the user content
    const { ciphertext, iv, rawKey } = await encryptText(content);

    // Prepare payload to send to the backend
    const payload = {
      iv: toBase64Url(iv),
      ciphertext: toBase64Url(ciphertext),
      expire: parseInt(expSel.value, 10) || 0,
      createdAt: Math.floor(Date.now() / 1000)
    };

    // Send encrypted data to the server
    const res = await fetch('save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!res.ok) throw new Error('Network error');
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Failed to save');

    // Build shareable URL with encryption key in the fragment (#)
    const keyB64 = toBase64Url(rawKey);
    const url = `${location.origin}${location.pathname}?id=${encodeURIComponent(data.id)}#${keyB64}`;

    // Show share UI
    sharelink.value = url;
    share.classList.remove('hidden');
    viewer.classList.add('hidden');
    decrypted.value = '';
  } catch (e) {
    console.error(e);
    alert('Could not create paste: ' + e.message);
  } finally {
    btnCreate.disabled = false;
  }
});

// Clear input field
btnClear.addEventListener('click', () => { txt.value = ''; });

// Copy link to clipboard
btnCopy.addEventListener('click', () => {
  sharelink.select();
  document.execCommand('copy');
});


// --- View Mode (when URL contains ?id=...#key) ---
async function tryView() {
  const params = new URLSearchParams(location.search);
  const id = params.get('id');
  const key = location.hash.startsWith('#') ? location.hash.slice(1) : '';
  if (!id || !key) return; // Not in view mode

  try {
    // Fetch encrypted data from server
    const res = await fetch('get.php?id=' + encodeURIComponent(id));
    const data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Not found');

    // Decrypt using the key from URL fragment
    const plain = await decryptText(data.ciphertext, data.iv, key);
    decrypted.value = plain;

    // Show viewer, hide share UI
    viewer.classList.remove('hidden');
    share.classList.add('hidden');
  } catch (e) {
    console.error(e);
    alert('Could not decrypt/open: ' + e.message);
  }
}

// Run viewer mode on page load
tryView();
