# Toffee Stock Management System

A PHP-based web application for managing toffee stock in a store.

## Features

- Add new toffee items with details (name, quantity, price, type, image)
- View all toffees in a grid layout with total cost calculation
- Update existing toffee information
- Delete toffee items
- Search functionality by name
- Sort by highest quantity or name
- Image upload support
- Responsive design

## Understanding the Fields

### What is "fourto"?
The "fourto" field in the original request appears to be a typo or unclear naming. In this system, it has been implemented as **"toffee_type"** which represents the category or type of toffee. Examples include:
- **Premium** - High-quality toffees
- **Standard** - Regular toffees
- **Deluxe** - Special edition toffees
- **Budget** - Economy toffees

You can use any descriptive text for this field based on your business needs.

## Setup Instructions

### 1. Database Setup

1. Open phpMyAdmin or MySQL client
2. Run the SQL queries from `database.sql`:
   ```sql
   CREATE DATABASE toffee_stock;
   USE toffee_stock;
   -- Then run the CREATE TABLE and INSERT statements
   ```

### 2. File Setup

1. Copy all files to your web server directory (e.g., `c:/xampp/htdocs/stocks/`)
2. Ensure the `uploads/` directory has write permissions
3. Update database credentials in `config.php` if needed

### 3. Access the Application

- Main page: `http://localhost/stocks/`
- Add new toffee: `http://localhost/stocks/add.php`
- Edit toffee: `http://localhost/stocks/edit.php`

## File Structure

```
stocks/
├── index.php          # Main page displaying all toffees
├── add.php            # Add new toffee form
├── edit.php           # Edit existing toffee
├── delete.php         # Delete toffee (backend)
├── config.php         # Database configuration
├── functions.php      # Utility functions
├── style.css          # CSS styles
├── database.sql       # SQL setup queries
├── uploads/           # Directory for uploaded images
└── README.md          # This file
```

## Usage

1. **Add Toffee**: Click "Add New Toffee" button
2. **Edit Toffee**: Click "Edit" button on any toffee card
3. **Delete Toffee**: Click "Delete" button (with confirmation)
4. **Search**: Use the search bar to find toffees by name
5. **Sort**: Use dropdown to sort by highest quantity or name

## SQL Queries Summary

### Create Database and Table
```sql
CREATE DATABASE toffee_stock;
USE toffee_stock;

CREATE TABLE toffees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    toffee_type VARCHAR(255),  -- This was the "fourto" field
    image_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Common Queries
- **Select all**: `SELECT * FROM toffees ORDER BY quantity DESC`
- **Search**: `SELECT * FROM toffees WHERE name LIKE '%search_term%'`
- **Insert**: `INSERT INTO toffees (name, quantity, price, toffee_type, image_path) VALUES (?, ?, ?, ?, ?)`
- **Update**: `UPDATE toffees SET name=?, quantity=?, price=?, toffee_type=?, image_path=? WHERE id=?`
- **Delete**: `DELETE FROM toffees WHERE id=?`
