# Jak wpiąłbym tę wycenę w sklep oparty na PrestaShop?

Do PrestaShop dopisałbym osobny moduł zapewniający formularz dla użytkownika, który komunikowałby się z API wyceny. Frontend przesyłałby format, papier, nakład i termin realizacji i nie obliczałby ceny samodzielnie. W API należałoby też zadbać o odpowiednią autentyfikację oraz integrację z systemem użytkowników w PrestaShop, aby wyświetlać cenę netto dla klientów B2B i odpowiednio naliczać rabat lojalnościowy.

# 1. Zastosowane uproszczenia

Na potrzeby zadania przyjmowałem następujące uproszczenia:

- Dane (cennik, archiwum zamówień, baza klientów) przechowuję w pliku JSON zamiast w bazie danych.
- Pomijam autentyfikację w endpointach.
- Klienta identyfikuję poprzez parametr customerId

# 2. Uruchamianie aplikacji

uruchomienie kontenerów

```docker compose up -d```

instalowanie zależności:

```docker exec symfony_app composer install```

# 3. Struktura aplikacji

```text
app/
├── src/
│   ├── Controller/
│   │   ├── PriceController.php
│   │   └── PriceListController.php
│   │
│   ├── Dto/
│   │   └── PriceQuoteRequest.php
│   │
│   └── Service/
│       ├── CustomerStorage.php
│       ├── OrderStorage.php
│       ├── PriceListStorage.php
│       └── PricingService.php
│
├── tests/
│   ├── Controller/
│   │   ├── PriceControllerTest.php
│   │   └── PriceListControllerTest.php
│   │
│   └── Service/
│       ├── CustomerStorageTest.php
│       ├── OrderStorageTest.php
│       ├── PriceListStorageTest.php
│       └── PricingServiceTest.php
│
├── var/
│   └── data/
│       ├── customers.json
│       ├── orders.json
│       └── price-list.json
│
├── AI.md
├── DECYZJE.md
└── README.md
```

`Controller/PriceController.php` - obsługa endpointu do wyceny.

`Controller/PriceListController.php` - obsługa endpointów do modyfikacji i odczytu cennika.

`Dto/PriceQuoteRequest.php` - DTO do wyceny.

`Service/CustomerStorage.php`  
`Service/OrderStorage.php`  
`Service/PriceListStorage.php`

 obsługa zapisu/odczytu danych z JSON-ów.

`Service/PricingServiceTest.php` - główna logika wyceny.


`data/customers.json` - plik przechowujący informacje o klientach (czy są b2b, czy są stałymi klientami)

`data/orders.json` - archiwum zamówień

`data/price-list.json` - cennik

# 4. Przykładowe requesty

## POST /api/price/quote

Wykonuje wycenę i zapisuje ją

Przykład:

```http
POST /api/price/quote
```

```json
{
    "customerId": 2,
    "format": "A6",
    "paper": "Kreda 130 g",
    "quantity": 1000,
    "realizationDate": "2099-01-05"
}
```

### Odpowiedź

```json
{
    "orderId": "e5a423f2d51e3140",
    "createdAt": "2026-10-05T07:57:02+00:00",
    "customerId": 2,
    "b2b": false,
    "format": "A6",
    "paper": "Kreda 130 g",
    "quantity": 1000,
    "basePrice": 19,
    "realizationDate": "2099-01-05",
    "express": false,
    "discounts": {
        "quantity": 10,
        "loyalty": 5
    },
    "surcharges": {
        "express": 0
    },
    "net": 162.45,
    "vat": 37.36,
    "gross": 199.81
}
```

## GET /api/price-list

Zwraca aktualny cennik.

### Odpowiedź

```json
{
    "A6": {
        "Kreda 130 g": 19,
        "Kreda 170 g": 23,
        "Offset 90 g": 15
    },
    "DL": {
        "Kreda 130 g": 24,
        "Kreda 170 g": 29,
        "Offset 90 g": 19
    },
    "A5": {
        "Kreda 130 g": 32,
        "Kreda 170 g": 38,
        "Offset 90 g": 26
    },
    "A4": {
        "Kreda 130 g": 58,
        "Kreda 170 g": 69,
        "Offset 90 g": 47
    }
}
```

## PUT /api/price-list/{format}/{paper}

Aktualizuje cenę w cenniku.

Przykład:

```http
PUT /api/price-list/A4/Kreda%20170%20g
```

```json
{
    "price": 75
}
```

### Odpowiedź

```json
{
    "format": "A4",
    "paper": "Kreda 170 g",
    "price": 75
}
```

# 5. Testy

## Wszystkie testy

W folderze app:

```bash
php bin/phpunit
```

---

### PricingService

```bash
php bin/phpunit tests/Service/PricingServiceTest.php
```

### PriceListStorage

```bash
php bin/phpunit tests/Service/PriceListStorageTest.php
```

### CustomerStorage

```bash
php bin/phpunit tests/Service/CustomerStorageTest.php
```

### OrderStorage

```bash
php bin/phpunit tests/Service/OrderStorageTest.php
```

---

### PriceController

```bash
php bin/phpunit tests/Controller/PriceControllerTest.php
```

### PriceListController

```bash
php bin/phpunit tests/Controller/PriceListControllerTest.php
```
