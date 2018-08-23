git pull && \
  yarn run encore dev &&  \
  php bin/console doctrine:migrations:diff && \
  php bin/console doctrine:migrations:migrate && \
  php bin/console cache:clear --no-warmup
