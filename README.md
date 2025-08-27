# 🔐 CryptNote

CryptNote is a **minimalist, self-hosted pastebin clone** with client-side encryption.  
All data is encrypted in the browser using **AES-256-GCM** before being sent to the server.  
The server only stores encrypted text and has **zero knowledge** of the original content.  

## ✨ Features
- 🔑 **End-to-End Encryption** — messages are encrypted/decrypted only in the browser.  
- ⏳ **Expiration** — pastes can expire automatically after a defined time.  
- 📜 **Clean UI** — simple, responsive design powered by Tailwind CSS.  
- 🚫 **Server Has No Knowledge** — only encrypted data is stored on the server.  
- ⚡ **Lightweight & Fast** — uses PHP + flat JSON files (no database required).  

## 🚀 How it works
1. User writes a note in the browser.  
2. CryptNote encrypts the note with a random **AES-256-GCM key**.  
3. The encrypted content and IV are stored on the server (`/data/*.json`).  
4. A unique shareable link is generated:  
```

https://yourdomain.com/?id=xxxxxx#encryptionkey

```
- The server only sees the ciphertext.  
- The decryption key is kept in the **URL fragment (`#`)**, never sent to the server.  

## 📂 Project Structure
```

CryptNote/
│── index.html        # Main frontend (UI with Tailwind CSS + JS logic)
│── script.js         # Handles encryption, decryption, and paste creation
│── save.php          # API endpoint to save encrypted pastes
│── get.php           # API endpoint to retrieve encrypted pastes
│── utils.php         # Utility functions (base64url, JSON output, etc.)
│── data/             # Encrypted pastes are stored here as JSON files
│── .htaccess         # Protects JSON files and disables directory listing
│── LICENSE           # GPL v3 license
│── README.md         # Project documentation

````

## ⚙️ Requirements
- PHP 7.4+ (or newer, PHP 8.x recommended)  
- Web server with `.htaccess` support (Apache or Nginx with equivalent rules)  
- No database required (flat file storage)  

## 🛠️ Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/GoatCrazy9/CryptNote.git

2. Place it in your web server directory (e.g., `/var/www/html/cryptnote`).
3. Ensure the `data/` directory is **writable by the server**:

   ```bash
   chmod 775 data
   ```
4. Access your app in the browser:

   ```
   http://localhost/cryptnote
   ```
   
## 🔒 Security Notes

* The server **never sees the encryption key** (stored in the URL fragment).
* JSON files are protected with `.htaccess` rules (`Options -Indexes` + deny access).
* Always serve CryptNote over **HTTPS** to avoid leaking keys in plaintext traffic.

## 📜 License

This project is licensed under the **GNU General Public License v3.0**.
See the [LICENSE](LICENSE) file for details.

## 💡 Credits

* Inspired by [PrivateBin](https://privatebin.info/) (open-source pastebin with encryption).
* Built as a learning project to demonstrate **secure, minimal, end-to-end encrypted note sharing**.

## 📬 Contact Me

If you have questions, ideas, or suggestions, feel free to reach out:

- GitHub: [GoatCrazy9](https://github.com/GoatCrazy9)  
- Email: mauriciourquiza0@gmail.com
- Telegram: @Hadi151101
