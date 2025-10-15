create table client (
    id_client PRIMARY KEY NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    loyalty_point INT NOT NULL DEFAULT 0,
    phone VARCHAR(15) NOT NULL UNIQUE,
    id_document VARCHAR(100),
    id_user INT(100) foreign KEY
)
create table user (
    id_user PRIMARY KEY NOT NULL AUTO_INCREMENT,
    password VARCHAR(100) NOT NULL,
    role VARCHAR(100),
    surname VARCHAR(100),
)
create table reservation (
    id_reservation PRIMARY KEY NOT NULL AUTO_INCREMENT,
    number_of_room INT(10),
    room_number int(10) foreign key,
    check_in_date date,
    total_price int(10),
    booking_status VARCHAR(50)
    id_client INT(10) foreign KEY,
    booking_date date,
    check_out_date date,
    extra_services VARCHAR(100),
)
create table room (
    room_number int(10) primary key not null AUTO_INCREMENT,
    state VARCHAR (100),
    type VARCHAR(100),
    price int(100), 
    ameneties VARCHAR(100),
    capacity int(10)
    maintenance_status VARCHAR(100),
)
create table hotel_extra_services (
    extra_services VARCHAR(100) primary key not null AUTO_INCREMENT,
    service_type VARCHAR(100),
    price int(100),
    description VARCHAR(255)
    invoices varchar(255) foreign key
)
create table payment_management(
    id_payment int(10) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    payment_method VARCHAR(100),   
    invoices VARCHAR(100),
    payment_status VARCHAR(100),
    reservation_id int(10) foreign key,
    review VARCHAR(255) foreign key,
)
create table feedback(
    id_feedback int(10) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    review varchar(255),
    satisfaction_level int(10),
    feedback_date date,
)
create table statistic(
    id int(10) PRIMARY KEY NOT NULL AUTO_INCREMENT,
    season VARCHAR(100),
    trends VARCHAR(100),
    type varchar(100) foreign key,
)