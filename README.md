## REST API for Rcom

## Clone this repo

```
$ git clone https://github.com/fahim-ryans/rcom-api.git
```

## Install composer packages

```
$ cd rcom-api
$ composer install
```

## API Endpoints

```
$ Login                 : api/v1/customer/login
$ Method                : POST
$ Data                  : code, phone, password

$ Register              : api/v1/customer/register
$ Method                : POST
$ Data                  : code, phone, password

$ Logout                : api/v1/customer/logout
$ Method                : POST
$ Bearer                : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx

$ Purchase History      : api/v1/customer/purchase-history
$ Method                : GET
$ Bearer                : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx

$ Customer Support      : api/v1/customer/customer-support
$ Method                : POST
$ Data                  : bill_date, bill_no, brand_name, inv_code, item_name, item_type_name, location, operator, qty, ser_no, description
$ Bearer                : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx

$ Claims Report         : api/v1/customer/customer-claims
$ Method                : GET
$ Bearer                : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx

$ Account Information   : api/v1/customer/account-information
$ Method                : GET
$ Bearer Type           : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx

$ Change Password       : api/v1/customer/change-password
$ Method                : POST
$ Data                  : old_password, new_password, confirm_password
$ Bearer Type           : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx

$ Profile Update        : api/v1/customer/profile-update
$ Method                : POST
$ Data                  : customer_name, email, customer_address
$ Bearer Type           : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx
key                     : value
Mendatory fields are
customer_name           : abc
email                   : abc@gmail.com
customer_address: xyz

optional fields are
newPassword             : xxxxxx
confirmedPassword       : xxxxxx

$ Customer Order        : api/v1/customer/orders
$ Method                : GET
$ Bearer Type           : TOKEN xxxxxxxxxxxxxxxxxxxxxxxxx
```
