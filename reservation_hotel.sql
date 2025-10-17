CREATE DATABASE IF NOT EXISTS reservation_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservation_hotel;

CREATE TABLE user (
    id_user INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    password VARCHAR(100) NOT NULL,
    role VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL
);

CREATE TABLE client (
    id_client INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    loyalty_point INT NOT NULL DEFAULT 0,
    phone VARCHAR(15) NOT NULL UNIQUE,
    id_document VARCHAR(100),
    id_user INT,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE SET NULL
);

CREATE TABLE room (
    room_number INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    state VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    amenities VARCHAR(255),
    capacity INT NOT NULL,
    maintenance_status VARCHAR(100) NOT NULL
);

CREATE TABLE reservation (
    id_reservation INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    number_of_rooms INT NOT NULL,
    room_number INT NOT NULL,
    check_in_date DATE NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    booking_status VARCHAR(50) NOT NULL,
    id_client INT NOT NULL,
    booking_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    extra_services VARCHAR(255),
    FOREIGN KEY (room_number) REFERENCES room(room_number) ON DELETE CASCADE,
    FOREIGN KEY (id_client) REFERENCES client(id_client) ON DELETE CASCADE
);

CREATE TABLE hotel_extra_services (
    id_service INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    invoices VARCHAR(255)
);

CREATE TABLE payment_management (
    id_payment INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    payment_method VARCHAR(100) NOT NULL,
    invoices VARCHAR(100),
    payment_status VARCHAR(100) NOT NULL,
    reservation_id INT NOT NULL,
    review VARCHAR(255),
    FOREIGN KEY (reservation_id) REFERENCES reservation(id_reservation) ON DELETE CASCADE
);

CREATE TABLE feedback (
    id_feedback INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    review VARCHAR(255),
    satisfaction_level INT CHECK (satisfaction_level BETWEEN 1 AND 5),
    feedback_date DATE NOT NULL
);

CREATE TABLE statistic (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    season VARCHAR(100) NOT NULL,
    trends VARCHAR(100),
    type VARCHAR(100)
);