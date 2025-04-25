# Travel Management API

A comprehensive travel management solution built with Laravel and MySQL, providing streamlined business travel booking, expense tracking, and policy management.

## 🌟 Features

- **Authentication & User Management**
  - Role-based access control (Employee, Travel Admin, Super Admin)
  - User registration and profile management
  - Password reset functionality

- **Travel Booking**
  - Flight search and booking via TravelDuqa API
  - Hotel search and booking via Amadeus API
  - Car rental services

- **Travel Management**
  - Trip planning and organization
  - Booking management
  - Expense tracking and reporting
  - Policy compliance checking

- **Admin Features**
  - Company and department management
  - Travel policy creation and enforcement
  - Approval workflows for out-of-policy requests
  - User management and permissions

- **Additional Features**
  - Multi-language support
  - Notification preferences
  - Emergency contact management
  - Analytics for travel spending

## 🚀 Getting Started

### Prerequisites

- PHP 8.0+
- Composer
- MySQL
- Laravel 9+

### Installation

1. Clone the repository
   ```
   git clone https://github.com/yourusername/travel-management-api.git
   cd travel-management-api
   ```

2. Install dependencies
   ```
   composer install
   ```

3. Set up environment variables
   ```
   cp .env.example .env
   ```
   
4. Configure your database and API keys in `.env`
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   
   AMADEUS_API_KEY=your_amadeus_key
   AMADEUS_API_SECRET=your_amadeus_secret
   
   TRAVELDUQA_API_KEY=your_travelduqa_key
   ```

5. Generate application key
   ```
   php artisan key:generate
   ```

6. Run migrations
   ```
   php artisan migrate
   ```

7. Seed the database (optional)
   ```
   php artisan db:seed
   ```

8. Start the development server
   ```
   php artisan serve
   ```

## 📚 API Documentation

### Authentication Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/login` | User login |
| POST | `/api/register` | User registration |
| POST | `/api/logout` | User logout (requires auth) |
| POST | `/api/password/email` | Send password reset email |
| POST | `/api/password/reset` | Reset password |

### User Profile Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/profile` | Get user profile |
| PUT | `/api/profile` | Update user profile |
| PUT | `/api/profile/password` | Update password |
| PUT | `/api/profile/language` | Update language preference |
| PUT | `/api/profile/notifications` | Update notification preferences |

### Travel Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/flights/search` | Search for flights |
| POST | `/api/flights/book` | Book a flight |
| GET | `/api/hotels/search` | Search hotels by city |
| GET | `/api/hotels/autocomplete` | Autocomplete hotel search |
| POST | `/api/hotels/book` | Book a hotel |

### Trip Management Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/trips` | List all trips |
| POST | `/api/trips` | Create a new trip |
| GET | `/api/trips/{id}` | Get trip details |
| PUT | `/api/trips/{id}` | Update trip details |
| DELETE | `/api/trips/{id}` | Delete a trip |

### Expense Management Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/expenses` | List all expenses |
| POST | `/api/expenses` | Create a new expense |
| GET | `/api/expenses/{id}` | Get expense details |
| PUT | `/api/expenses/{id}` | Update expense details |
| DELETE | `/api/expenses/{id}` | Delete an expense |

### Admin Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/users` | List all users |
| POST | `/api/users` | Create a new user |
| GET | `/api/users/{id}` | Get user details |
| PUT | `/api/users/{id}` | Update user details |
| DELETE | `/api/users/{id}` | Delete a user |
| GET | `/api/policies` | List all policies |
| POST | `/api/policies` | Create a new policy |
| GET | `/api/companies` | List all companies (super admin) |
| POST | `/api/companies` | Create a new company (super admin) |

## 🔒 Role-Based Access Control

The API implements three user roles:

1. **Employee/Traveler**
   - Can manage their profile
   - Can create and manage trips
   - Can create and track expenses
   - Can view company policies

2. **Travel Admin**
   - All Employee permissions
   - Can manage users within their company
   - Can create and manage travel policies
   - Can approve/deny travel requests
   - Can update company settings

3. **Super Admin**
   - All Travel Admin permissions
   - Can manage multiple companies
   - Can manage departments
   - Can access system testing endpoints

## 🔄 External API Integrations

- **TravelDuqa API**: Used for flight search and booking
- **Amadeus API**: Used for hotel search and booking

## 📝 License

[MIT License](LICENSE)

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

## 📧 Contact

For any questions or support, please contact [jonasdeo02@gmail.com](mailto:jonasdeo02@gmail.com)
