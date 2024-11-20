# Enquiry Form Plugin

![WordPress](https://img.shields.io/badge/WordPress-6.3-blue?style=flat-square)
![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-blueviolet?style=flat-square)
![License](https://img.shields.io/github/license/keplrx/Enquiry-Form?style=flat-square)

A simple and customizable WordPress plugin that extends WooCommerce functionality by adding an **enquiry system** for products. Users can select items, add them to an enquiry cart, and submit their inquiries via a form.

## Features
- 🛒 **Custom Enquiry Cart**: Users can add products to an enquiry list.
- 📄 **Inquiry Form**: Captures user inquiries with additional fields.
- 📧 **Email Integration**: Sends enquiry details via email to admins and customers.
- 🔗 **WooCommerce Hooks**: Built on WooCommerce for seamless integration.
- 🎨 **Customizable Templates**: Modify enquiry form, cart display, and notification emails.

---

## Installation

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/keplrx/Enquiry-Form.git
   ```
2. Upload the plugin folder to your WordPress `/wp-content/plugins/` directory.
3. Activate the plugin through the WordPress admin dashboard:
   - Go to `Plugins` > `Installed Plugins`.
   - Find "Enquiry Form" and click **Activate**.

---

## Usage

1. Add products to your WooCommerce store.
2. The plugin automatically adds an **"Add to Enquiry"** button to product pages.
3. Users can view their selected items in the **Enquiry Cart**.
4. A form allows users to submit their inquiries. Both admin and the user receive email notifications.

---

## Screenshots

| Features                  | Screenshot                                                                                   |
|---------------------------|---------------------------------------------------------------------------------------------|
| Enquiry Cart View         | ![image](https://github.com/user-attachments/assets/7fe7e2cc-7d49-401f-b929-5f82a1cddaff)   |
| Enquiry Form Submission   | ![image](https://github.com/user-attachments/assets/6ce98c4e-59a2-4f2c-88b2-f79505b8cd97)   |
| Admin Notification Email  | ![image](https://github.com/user-attachments/assets/e38cd342-5656-4b15-8802-10d2298107c8)   |

---

## Configuration

### **Customizing the Enquiry Form**
- Modify the enquiry form template located in:
  ```
  /public/partials/ef-public-display-form.php
  ```

### **Email Settings**
- Email templates can be found in:
  ```
  /emails/
  ```
- Adjust styling or content in `enquiry-confirmation-template.php` and `enquiry-notification-template.php`.

---

## Development Setup

1. **Install Node.js** (optional for asset building).
2. **Run in Local Dev**:
   ```bash
   npm install
   npm run build
   ```
3. Make changes and test on your local WordPress installation.

---

## Contributing

Contributions are welcome! Feel free to fork the repository and submit a pull request.

---

## License

This project is licensed under the GNU General Public License. See the [LICENSE](LICENSE) file for details.

---

## Contact

For questions or issues, open an issue on GitHub or contact [keplrx](https://github.com/keplrx).

---

Let me know if you'd like me to customize or expand on any section further! 😊
