# BEKCAN Institute (Student) - WP EDU Client

🇹🇷 [Türkçe](https://github.com/canbekcan/wp-edu-client/blob/main/README-tr.md)

A client plugin that connects WordPress-based student websites to the main management system (Host LMS), providing student-focused content analytics, revision tracking, a centralized notice feed, and automated updates.

---

## 🚀 Features

* **Centralized LMS Integration:** Transmits content metrics, revision history, and publication statuses of student sites to the main dashboard.
* **User Authentication & SSO:** Secure API key verification via the Host LMS and Single Sign-On (SSO) support.
* **Centralized Notice Feed:** Pushes notices published by the instructor or institute directly to the student's WordPress dashboard.
* **Independent GitHub Updater:** A native update engine that operates without external library dependencies, checking for new GitHub releases and allowing direct updates from the WordPress dashboard.
* **Multilingual Support (i18n):** Full compatibility with Turkish (`tr_TR`) and English (`en_US`) language files.

---

## 📁 Directory Structure

```text
wp-edu-client/
├── admin/
│   ├── class-client-menu.php        # Admin menu and settings pages
│   └── view-client-settings.php     # Settings screen template
├── includes/
│   ├── api/
│   │   ├── class-client-auth.php              # API authorization layer
│   │   ├── class-client-endpoint-content.php  # Content synchronization endpoint
│   │   ├── class-client-endpoint-notices.php  # Notice synchronization endpoint
│   │   └── class-client-endpoint-updates.php  # Update notification endpoint
│   ├── class-client-github-updater.php        # GitHub Release update engine
│   ├── class-client-notices.php               # Dashboard notice mechanism
│   ├── class-client-sso.php                   # SSO verification mechanism
│   └── class-client-tracking.php              # Content and revision tracking
├── languages/
│   ├── wp-edu-client-en_US.mo
│   ├── wp-edu-client-en_US.po
│   ├── wp-edu-client-tr_TR.mo
│   └── wp-edu-client-tr_TR.po
├── LICENSE
├── README.md
├── uninstall.php
└── wp-edu-client.php                # Main plugin core file

```

---

## 🛠️ Installation

### Method 1: Installation via GitHub Release (Recommended)

1. Download the ZIP archive of the latest version from the project's [Releases](https://github.com/canbekcan/wp-edu-client/releases) page.
2. Go to the WordPress Admin Dashboard: **Plugins -> Add New Plugin -> Upload Plugin**.
3. Select the downloaded `.zip` file and click the **Install Now** button.
4. Activate the plugin.

### Method 2: Manual Installation

1. Download or clone this repository to your computer:

```bash
git clone [https://github.com/canbekcan/wp-edu-client.git](https://github.com/canbekcan/wp-edu-client.git)

```

2. Move the folder to the `wp-content/plugins/wp-edu-client` directory.
3. Activate the plugin via the WordPress dashboard.

---

## ⚙️ Configuration

1. Go to the **Connect LMS** tab that appears in the left menu of WordPress.
2. Enter the information provided to you by the Host LMS into the respective fields and save:

* **Host API URL**
* **API / Client Key**

3. Check the connection status to complete the integration.

---

## 🔄 Update Management

The plugin periodically queries the GitHub API. When a new Release is published, it generates standard WordPress notifications on the **Dashboard -> Updates** and **Plugins** screens. You can complete the update with a single click directly from the dashboard.

---

## 📋 Requirements

* **PHP:** 7.4 or higher
* **WordPress:** 6.0 or higher
* **PHP Extensions:** `cURL`, `OpenSSL`, `JSON`

---

## 📄 License

This project is licensed under the [MIT License](https://www.google.com/search?q=LICENSE).

```text
MIT License

Copyright (c) 2026 Can Bekcan / BEKCAN Institute

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

```