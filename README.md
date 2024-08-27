# Laravel Passkey Example

This repository demonstrates how to implement Passkey authentication in a Laravel application. Passkeys offer a secure, passwordless authentication mechanism that improves security and user experience.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## Requirements

- PHP >= 8.2

## Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/karan-darji/passkey-example.git
   cd passkey-example
2. **Install dependencies**
    ```bash
    composer install
3. **Set up the database**
- Update your .env file with the correct database credentials.
- Run the migrations:
    ```bash
    php artisan migrate
4. **Install npm dependencies and compile assets**
    ```bash
    npm install && npm run dev
## Usage
1. Managing Passkeys:
    - Users can add, remove, or manage their passkeys via the account profile page.
2. Login with Passkey:
    - Users can log in using their passkey instead of a traditional password.
## Troubleshooting
- Common Issues:

    - Ensure your server is running on HTTPS, as WebAuthn requires secure communication.
    - If you encounter issues with browser support, make sure you're using a compatible version.
- Error Logs:
    -  Check the Laravel logs in storage/logs/laravel.log for any errors or issues.
## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
    



