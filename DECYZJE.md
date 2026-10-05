# Wymagania

- Możliwy nakład od 100 - 20 000 szt
- Minimalna wartość zamówienia 50zł
- Rabaty ilościowe: 10% powyżej 1000szt, 15% od 5000 szt
- Rabat lojalnościowy: 5%
- Usługa Ekspres (realizacja 24h) +30% do ceny i brak rabatowania
- Usługa musi zwracać ceny brutto i netto
- Zamówienia muszą być zapisywane
- Dział sprzedaży musi mieć możliwość modyfikacji cennika

# Niejasności i sprzeczności

> **Pytania, które zadałbym osobie z biznesu:**

- Czy API ma wyceniać pojedynczy typ produktu czy ma uwzgledniać zamówienia mieszane?

Przyjmuję założenie, że ma wyceniać pojedynczy typ produktu

- Czy rabaty obowiązują od 1000 sztuk czy powyżej 1000 sztuk?

Przyjmuję założenie że od 1000 sztuk włącznie

- W jaki sposób mają być naliczane rabaty? (czy od ceny netto, czy brutto, jak mają być zaokrąglane)

Zakładam że rabatujemy od ceny netto podanej liczby sztuk (skoro cennik bazowy na nich się opiera), obliczenia wykonuję na wysokiej precyzji a następnie zaokrąglam do 2 miejsc po przecinku

- Czy można kupić liczbę sztuk która nie jest wielokrotnością 100?

Przyjmuję, że nie bo cennik tego nie uwzględnia

- W jaki sposób sprawdzamy czy użytkownik jest stałym klientem lub klientem B2B?

Zakładam, że ta informacja już znajduje się w bazie danych i mogę ją uzyskać na podstawie id użytkownika

- Czy minimalna wartość zamówienia 50zł to brutto czy netto. Czy uwzględniamy w niej rabat?

Zakładam że chodzi o minimalną wartość netto po uwzględnieniu rabatów/dopłaty za ekspres

- Skoro dla klientów B2B pokazujemy cenny netto to w jaki sposób mam zwracać ceny?

Zakładam, że zwracam zarówno brutto i netto a to frontend decyzuje co prezentować

- Czy rabat lojalnościowy sumuje się z ilościowym?

Zakładam, że tak

- Jeżeli rabat lojalnościowy sumuje się z ilościowym to w jaki sposób?

Przyjmuję założenie, że rabat lojalnościowy jest nakładany na kwotę zrabatowaną przez rabat ilościowy.

- Czy na pewno wykluczamy wszystkie rabaty w usłudze Ekspres (i nie zostawiamy np lojalnościowego)?

Zakładam, że tak

- W jaki sposób dział sprzedaży miałby modyfikować cennik?

Przyjmuję założenie, że cennik jest przechowywany w jakiejś bazie danych/pliku a ja muszę zapewnić endpointy umożliwiające modyfikację tego cennika

> **Pozostałe problemy:**

- Frontend absolutnie nie może walidować kwoty zamówienia bo użytkownik może modyfikować skrypt JS. Trzeba zrobić walidację po stronie backendu ale do tego trzeba mieć pełny zestaw danych (format, papier, nakład, termin) a nie tylko gotową kwotę.

Przyjmuję założenie, że dostaję pełny zestaw danych bez gotowej kwoty (i tak ona mi nic nie daje)

- Kierownik sprzedazy nie wspomina Czy rabatowania dla zamówień ekspres.

Przyjmuję założenie, że zamówień ekspres nie rabatujemy w żaden sposób skoro się to nie opłaca

# Na potrzeby zadania przyjmuję następujące uproszczenia

- Cennik przechowuję w pliku json zamiast w bazie danych
- Pomijam autentyfikację w enpointach
- Klienta identyfikuję poprzez parametr customerId