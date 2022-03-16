# Implementační dokumentace k 1. úloze do IPP 2021/2022

---
Jméno a příjmení: Anton Medvedev \
Login: xmedve04

## Použití

---
Program `parse.php` podporuje jeden argument prikazove radky:
```
--help
```
Pri jeho pouziti program vypise strucnou napocedu jak program pouzivat.\
Pro prace s programem musime pridat nejaky fajl v jazyce `IPPcode22`
na `STDIN`.\
Behem sve prace program vypise reprezentace kodu v `xml` formatu na standartni
vysput. A ukonci cinnost s `0` navratovem kodem.\
Jestli nastane chyba, program ukonci cinnost s chybovem kodem podle druhu chyby.

## Implementace

---
Na zacatku program skonroluje spravnost agrumentu prikazove radky.\
Dal program bude nacitat a spracovyvat vstupni fajl po radku a zacne 
svou cinnost po podkani radku, ktere ma retezec `.IPPcode22`. \
Hlavna funkcnost prohazi v funkce - `analyze_instruction`
, ktera rozdeli line na casti, a provede kontroly podle typu instukce.\
Jestli to povede uspesne, program vypise instrukce v `xml` formatu.
