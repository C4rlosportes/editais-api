# Editais API

API REST desenvolvida em Laravel para gerenciamento de editais, requisitos, destinos, inscrições e processos de análise.

O sistema possui autenticação com Laravel Sanctum e controle de acesso por perfil, contemplando os perfis SME e MILITAR.

## Tecnologias

- PHP 8.2
- Laravel 12
- PostgreSQL 17
- Laravel Sanctum
- Docker e Docker Compose
- PHPUnit / Pest

## Arquitetura

O projeto segue uma organização em camadas:

- **Controllers:** recebem as requisições HTTP.
- **Form Requests:** validam os dados recebidos.
- **Services:** concentram as regras de negócio.
- **Repositories:** centralizam as operações de persistência.
- **Models:** representam as entidades e relacionamentos.
- **Resources:** padronizam as respostas JSON da API.

## Executando com Docker

### Pré-requisitos

- Git
- Docker Desktop instalado e em execução.
- Docker Compose disponível.

Os comandos abaixo utilizam PowerShell no Windows.

### 1. Clonar o repositório

```powershell
git clone https://github.com/C4rlosportes/editais-api.git
cd editais-api
```

### 2. Criar o ambiente Docker

Copie o arquivo de exemplo:

```powershell
Copy-Item .env.docker.example .env.docker
```

Abra o arquivo:

```powershell
notepad .env.docker
```

Configure uma senha local forte e exclusiva em `DOCKER_DB_PASSWORD`.

Não compartilhe nem versione o arquivo `.env.docker`.

### 3. Validar a configuração

```powershell
docker compose --env-file .env.docker config --quiet
```

Se o comando terminar sem erros, a configuração está válida.

### 4. Construir e iniciar os containers

```powershell
docker compose --env-file .env.docker up -d --build
```

O ambiente contém dois serviços:

| Serviço | Porta no Windows | Porta no container |
|---|---:|---:|
| Laravel | 8080 | 8000 |
| PostgreSQL | 5433 | 5432 |

O container Laravel instala as dependências com Composer na primeira inicialização e inicia o servidor com `php artisan serve`.

Acesse a API:

```text
http://localhost:8080
```

### 5. Executar as migrations

```powershell
docker compose --env-file .env.docker exec app php artisan migrate
```

### 6. Popular o banco de dados

Para inserir os dados fictícios de desenvolvimento:

```powershell
docker compose --env-file .env.docker exec app php artisan db:seed
```

Os seeders cadastram OPMs, editais, militares, requisitos e destinos.

As contas de autenticação não são criadas automaticamente pelos seeders.

## Banco de dados

O PostgreSQL utilizado pelo Docker é independente do PostgreSQL instalado localmente.

Configuração para acesso pelo Windows ou por ferramentas como TablePlus:

| Configuração | Valor |
|---|---|
| Host | localhost |
| Porta | 5433 |
| Banco | editais_api_docker |
| Usuário | editais_docker |
| Senha | Definida em `.env.docker` |

Dentro do container Laravel, a conexão utiliza o host `pgsql` e a porta `5432`.

Os dados do PostgreSQL são armazenados em um volume persistente do Docker.

## Testes automatizados

A suíte de testes utiliza SQLite em memória.

Para executar os testes dentro do container, utilize:

```powershell
docker compose --env-file .env.docker exec `
  -e APP_ENV=testing `
  -e DB_CONNECTION=sqlite `
  -e DB_DATABASE=:memory: `
  -e DB_URL= `
  app php artisan test
```

Essa configuração mantém os testes isolados do PostgreSQL utilizado pela aplicação.

## Endpoints principais

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/editais` | Lista os editais |
| GET | `/api/editais/{edital}` | Consulta um edital |
| GET | `/api/opms` | Lista as OPMs |
| POST | `/api/login` | Autenticação |
| GET | `/api/me` | Consulta o usuário autenticado |
| POST | `/api/logout` | Encerra a sessão do token atual |

As rotas protegidas utilizam autenticação Bearer Token com Laravel Sanctum.

O acesso às operações é controlado conforme o perfil do usuário.

## Comandos úteis do Docker

Consultar o estado dos containers:

```powershell
docker compose --env-file .env.docker ps
```

Consultar os logs do Laravel:

```powershell
docker compose --env-file .env.docker logs app
```

Consultar os logs do PostgreSQL:

```powershell
docker compose --env-file .env.docker logs pgsql
```

Parar os containers:

```powershell
docker compose --env-file .env.docker stop
```

Iniciar novamente:

```powershell
docker compose --env-file .env.docker up -d
```

Parar e remover os containers e a rede:

```powershell
docker compose --env-file .env.docker down
```

O comando `down` preserva os volumes e os dados do banco.

**Atenção:** não utilize `docker compose down -v` sem intenção de apagar os volumes, pois isso remove também os dados persistidos do PostgreSQL e as dependências armazenadas no volume `vendor`.

## Segurança

- Não versione arquivos `.env` com credenciais.
- Utilize `.env.docker.example` como modelo de configuração.
- Mantenha senhas e tokens fora do controle de versão.
- Utilize contas fictícias para os testes locais.
