# Wykorzystywane narzędzia

- plugin Continue z Claude Sonnet 5
- GPT-5.6 Luna

# Co odrzuciłem z AI

- Przesyłanie informacji o tym, czy użytkownik jest stałym klientem, w requeście
- Problematyczne wyliczanie, czy zamówienie jest ekspresowe (AI założył, że termin realizacji to data wykonania wyceny)
- AI uwzględniało w terminie realizacji godzinę co powodowało komplikację przy określaniu czy zamówienie jest ekspresowe

# Co poprawiłem po AI

- Testy nie uwzględniały przypadków granicznych
- Testy nie uwzględniały „resetowania” plików JSON
- Dodanie ID klienta w requeście
- Dodanie pobierania z pliku informacji o tym, czy klient składa zamówienia B2B i czy jest stałym klientem
- Dodanie informacji w response o cenie bazowej, dopłatach oraz informacji o tym, czy klient składa zamówienie B2B
- AI miał problem z ustawieniem odpowiednich typów w testach
- AI nie uwzględnił zapisywania zamówień