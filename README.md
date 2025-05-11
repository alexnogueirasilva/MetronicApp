# Metronic API

[![Laravel](https://img.shields.io/badge/Laravel-12.0-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel Octane](https://img.shields.io/badge/Octane-2.9-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/10.x/octane)
[![Laravel Horizon](https://img.shields.io/badge/Horizon-5.31-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com/docs/10.x/horizon)
[![Redis](https://img.shields.io/badge/Redis-Support-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io)
[![PestPHP](https://img.shields.io/badge/PestPHP-3.8-8A2BE2?style=for-the-badge&logo=php&logoColor=white)](https://pestphp.com)

## Overview

Metronic API is a high-performance RESTful API backend built with Laravel. The application provides robust authentication, authorization, and access control features, making it ideal for modern web applications requiring secure API functionality.

## Features

### Authentication System
- Standard email/password login
- Two-factor authentication with Time-based One-Time Password (TOTP)
- Password reset functionality
- JWT authentication via Laravel Sanctum
- Email verification

### Access Control
- Role-based access control (ACL)
- Granular permission system
- Role management API
- Permission management API

### Performance
- High-performance API with Laravel Octane
- RoadRunner server integration
- Asynchronous job processing with Laravel Horizon
- Redis integration for caching and queue management

### Security
- TOTP (Time-based One-Time Password) implementation
- Email OTP verification
- Password policy enforcement
- Sanctum token-based authentication

### Developer Features
- API documentation with Scribe
- Comprehensive test suite with PestPHP
- Static analysis with PHPStan
- Code formatting with Pint
- Git hooks via CaptainHook

### Geolocation
- Geolocation capabilities for tracking and location-based services

## Technical Stack

- **Framework:** Laravel 12.0
- **PHP Version:** 8.2+
- **Performance:** Laravel Octane 2.9 with RoadRunner
- **Queue Processing:** Laravel Horizon 5.31
- **Authentication:** Laravel Sanctum 4.0
- **Database:** Supports MySQL, MariaDB, PostgreSQL (SQLite for testing)
- **Caching & Queue:** Redis
- **Testing:** PestPHP 3.8
- **2FA/OTP:** spomky-labs/otphp 11.3
- **API Documentation:** knuckleswtf/scribe 5.2

## API Endpoints

### Authentication
- POST `/api/auth/login` - User login
- DELETE `/api/auth/logout` - User logout
- GET `/api/auth/me` - Get authenticated user info
- POST `/api/auth/forgot-password` - Request password reset
- POST `/api/auth/reset-password` - Reset password

### OTP/2FA
- POST `/api/auth/otp/request` - Request email verification code
- POST `/api/auth/otp/verify` - Verify email code
- POST `/api/auth/otp/totp/setup` - Setup TOTP authentication
- POST `/api/auth/otp/totp/verify` - Verify TOTP code
- POST `/api/auth/otp/totp/confirm` - Confirm TOTP setup
- POST `/api/auth/otp/disable` - Disable OTP authentication

### Access Control
- GET `/api/acl/role` - List all roles
- GET `/api/acl/role/{id}` - Get role details
- POST `/api/acl/role` - Create new role
- PUT `/api/acl/role/{id}` - Update role
- DELETE `/api/acl/role/{id}` - Delete role
- GET `/api/acl/permission` - List all permissions
- GET `/api/acl/permission/{id}` - Get permission details
- POST `/api/acl/permission` - Create new permission
- PUT `/api/acl/permission/{id}` - Update permission
- DELETE `/api/acl/permission/{id}` - Delete permission