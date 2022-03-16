# Implementační dokumentace k 1. úloze do IPP 2021/2022

---
Jméno a příjmení: Anton Medvedev \
Login: xmedve04

## Použití

---
Program `parse.php` podporuje jeden argument přikazové řadky:
```
--help
```
Při jeho použití program vypíše stručnou nápovědu, jak program používat.\
Pro práci s programem musíme přidat nějaký soubor v jazyce `IPPcode22`
na `STDIN`.\
Během běhu, program vypíše reprezentaci kodu v `xml` formátu na standartní
výstup. A ukončí činnost s `0` návratovým kódem.\
Jestli nastane chyba, program ukončí činnost s chybovým kódem podle druhu chyby.

## Implementace

---
Na začátku program zkonroluje správnost agrumentů příkazové řádky.\
Dál program bude načítat a zpracovávat vstupni soubor po řádku a začne 
svou činnost po tom, co narazí na řádek, který ma řetězec `.IPPcode22`. \
Hlavní funkčnost se nachází ve v funkci - `analyze_instruction`
, která rozdělí line na části a provede kontroly podle typu instukce.\
Pokud se to povedlo úspěšně, program vypíše instrukce v `xml` formátu.
