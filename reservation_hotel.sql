SHOW DATABASES;

CREATE DATABASE IF NOT EXISTS reservation_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservation_hotel;

CREATE TABLE IF NOT EXISTS guest ( -- fait
    guest_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    loyality_points INT NOT NULL DEFAULT 0,
    phone VARCHAR(15) NOT NULL UNIQUE,
    adress VARCHAR(100),
    login INT,
    password VARCHAR(100) NOT NULL,
    registration_date DATE NOT NULL,
    date_of_birth DATE NOT NULL
);

CREATE TABLE IF NOT EXISTS room ( -- fait
    room_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    room_number INT(10) NOT NULL UNIQUE,
    status VARCHAR(100) NOT NULL,
    room_type_id INT(10) NOT NULL, -- FK to room_type
    price_per_night DECIMAL(10,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS reservation ( -- fait
    id_reservation INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    number_of_guest INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    room_id INT NOT NULL, -- FK to room
    total_price INT(10) NOT NULL,
    booking_date DATE NOT NULL,
    guest_id INT NOT NULL -- FK to client
);

CREATE TABLE IF NOT EXISTS services ( -- fait
    service_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS payment ( -- fait
    payment_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    method VARCHAR(100) NOT NULL,
    reservation_id INT NOT NULL, -- FK to reservation
    guest_id INT NOT NULL, -- FK to guest
    amount INT(10) NOT NULL,
    payment_date DATE NOT NULL,
    status VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS feedback ( -- fait
    feedback_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    comment VARCHAR(255),
    rating INT CHECK (rating BETWEEN 1 AND 5),
    date_posted DATE NOT NULL,
    guest_id INT NOT NULL, -- FK to guest
    reservation_id INT NOT NULL -- FK to reservation
);

CREATE TABLE IF NOT EXISTS employee ( -- fait
    phone VARCHAR(15) NOT NULL,
    employee_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
    login VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS room_type ( -- fait
    room_type_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    type_name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    amenities VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS maintenance ( -- fait
    maintenance_id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    room_id INT NOT NULL, -- FK to room
    description VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE,
    employee_id INT NOT NULL
);
