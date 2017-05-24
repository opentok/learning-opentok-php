:: Why? because windows can't do an OR within the conditional
IF NOT DEFINED TOKBOX_API_KEY GOTO defkeysecret
IF NOT DEFINED TOKBOX_SECRET GOTO defkeysecret
GOTO skipdef

:defkeysecret

SET TOKBOX_API_KEY=
SET TOKBOX_SECRET=

:skipdef

RD /q /s storage

php.exe -S localhost:8080 -t web web/index.php
