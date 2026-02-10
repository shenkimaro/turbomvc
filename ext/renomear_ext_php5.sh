#!/bin/bash

# Navega até o diretório onde estão os arquivos
#cd /caminho/para/sua/pasta || exit

# Loop através de todos os arquivos .php5
for arquivo in *.php5; do
    # Verifica se o arquivo existe (evita erro quando não há arquivos)
    if [ -e "$arquivo" ]; then
        # Remove a extensão .php5 e adiciona .php
        novo_nome="${arquivo%.php5}.php"
        mv "$arquivo" "$novo_nome"
        echo "Renomeado: $arquivo -> $novo_nome"
    fi
done

echo "Processo concluído!"
