## Co se mění

<!-- Stručně, jeden účel. Odkaz na issue. -->

## Proč

<!-- Odkaz na UC/BR nebo vysvětlení, proč je změna potřeba. -->

## Jak otestováno

- [ ] ruční scénář v prohlížeči
- [ ] negativní případ
- [ ] unit testy / `php -l`

## Rizika a dopady

<!-- Co může rozbít, co je potřeba zkontrolovat při deployi. -->

## Checklist

- [ ] Server validuje vstupy a zachovává hodnoty při chybě.
- [ ] POST má CSRF a správnou kontrolu práv, včetně vlastnictví.
- [ ] SQL je prepared, výstup escapovaný.
- [ ] Vícekrokové operace jsou transakční.
- [ ] UI je česky, responzivní, bez chyb v konzoli.
- [ ] Dokumentace a mapování UC jsou aktuální.
- [ ] Autor umí vysvětlit každý změněný řádek.
