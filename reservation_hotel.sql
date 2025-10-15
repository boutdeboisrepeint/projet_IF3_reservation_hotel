CREATE DATABASE IF NOT EXISTS reservation_hotel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reservation_hotel;

CREATE TABLE app_user (
    id_user INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    password VARCHAR(100) NOT NULL,
    role VARCHAR(100),
    surname VARCHAR(100)
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
    FOREIGN KEY (id_user) REFERENCES app_user(id_user) ON DELETE SET NULL
);

CREATE TABLE room (
    room_number INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    state VARCHAR(100),
    type VARCHAR(100),
    price DECIMAL(10,2),
    ameneties VARCHAR(255),
    capacity INT,
    maintenance_status VARCHAR(100)
);

CREATE TABLE reservation (
    id_reservation INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    number_of_room INT,
    room_number INT,
    check_in_date DATE,
    total_price DECIMAL(10,2),
    booking_status VARCHAR(50),
    id_client INT,
    booking_date DATE,
    check_out_date DATE,
    extra_services VARCHAR(255),
    FOREIGN KEY (room_number) REFERENCES room(room_number),
    FOREIGN KEY (id_client) REFERENCES client(id_client)
);

CREATE TABLE hotel_extra_services (
    id_service INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    service_name VARCHAR(100),
    price DECIMAL(10,2),
    description VARCHAR(255),
    invoices VARCHAR(255)
);

CREATE TABLE payment_management (
    id_payment INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    payment_method VARCHAR(100),
    invoices VARCHAR(100),
    payment_status VARCHAR(100),
    reservation_id INT,
    review VARCHAR(255),
    FOREIGN KEY (reservation_id) REFERENCES reservation(id_reservation)
);

CREATE TABLE feedback (
    id_feedback INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    review VARCHAR(255),
    satisfaction_level INT,
    feedback_date DATE
);

CREATE TABLE statistic (
    id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
    season VARCHAR(100),
    trends VARCHAR(100),
    type VARCHAR(100)
);