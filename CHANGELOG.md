## Version 2.2.0 (not released yet)
#### Changes in config File
* const array GREMIUM_PREFIX
#### DB changes
 * konto.zweck changed to varchar(512)
 * changed foreign key from booking.zahlung_id -> konto.id to booking(zahlung_id, zahlung_type) -> konto(id, konto_id) 
 * ALTER TABLE finanzformular__beleg_posten DROP CONSTRAINT finanzformular__beleg_posten_ibfk_2; (projektposten_id FK)

#### new Features 
* TAN QR and TAN Flicker implemented

## Version 2.1.0
Introduced FINTS instead of Hibiscus Payment Server

# Version 2.0.0
whole rework from dynamic to more static system because of bad performance