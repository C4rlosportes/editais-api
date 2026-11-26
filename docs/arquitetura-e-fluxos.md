# Arquitetura e Fluxos Funcionais



## 1. Visão geral



A Editais API é uma aplicação REST desenvolvida em Laravel para gerenciamento de editais, requisitos, destinos, inscrições e processos de análise.



A aplicação possui autenticação baseada em Laravel Sanctum e autorização por perfil de acesso, contemplando os perfis SME e MILITAR.



## 2. Organização da arquitetura



O projeto utiliza uma arquitetura em camadas, separando responsabilidades:



| Camada | Responsabilidade |

|---|---|

| Routes | Define os endpoints e os middlewares aplicados. |

| Form Requests | Valida os dados recebidos nas requisições. |

| Controllers | Recebe as requisições HTTP e coordena as operações. |

| Services | Implementa as regras de negócio e os fluxos da aplicação. |

| Repositories | Centraliza consultas e operações de persistência. |

| Models | Representa as entidades e seus relacionamentos. |

| Resources | Padroniza a representação dos dados nas respostas JSON. |

| Middleware | Controla autenticação e autorização por perfil. |



### Fluxo de uma requisição



```text

Cliente HTTP

&#x20;    |

&#x20;    v

&#x20;  Routes

&#x20;    |

&#x20;    v

&#x20;Middleware

&#x20;    |

&#x20;    v

&#x20;Form Request

&#x20;    |

&#x20;    v

&#x20;Controller

&#x20;    |

&#x20;    v

&#x20; Service

&#x20;    |

&#x20;    v

&#x20;Repository

&#x20;    |

&#x20;    v

&#x20; Model / PostgreSQL

&#x20;    |

&#x20;    v

&#x20; Resource

&#x20;    |

&#x20;    v

&#x20;Resposta JSON

```



A sequência pode variar conforme a operação, mas essa estrutura representa a organização geral do projeto.



## 3. Entidades e relacionamentos



O domínio possui as seguintes entidades principais:



| Entidade | Responsabilidade |

|---|---|

| User | Representa a conta de acesso à aplicação. |

| Militar | Armazena os dados funcionais do militar. |

| OPM | Representa uma organização policial militar. |

| Edital | Representa o edital disponibilizado pela SME. |

| Requisito | Define os critérios que devem ser conferidos na inscrição. |

| Destino | Representa uma opção de destino disponibilizada em um edital. |

| Inscrição | Representa a participação de um militar em um edital. |

| Escolha | Armazena os destinos escolhidos e sua ordem de preferência. |

| Conferência | Registra a avaliação de cada requisito de uma inscrição. |

| Histórico | Registra eventos relevantes das operações do sistema. |



### Principais relacionamentos



\- Uma OPM pode possuir vários militares.

\- Um militar pertence a uma OPM de origem.

\- Uma OPM pode estar associada a vários destinos.

\- Um edital possui vários requisitos.

\- Um edital possui vários destinos.

\- Um edital pode possuir várias inscrições.

\- Uma inscrição pertence a um militar e a um edital.

\- Uma inscrição pode possuir várias escolhas.

\- Uma escolha referencia um destino e mantém sua ordem de preferência.

\- Uma inscrição possui conferências relacionadas aos requisitos do edital.

\- Uma conta User pode estar vinculada a um cadastro Militar.

\- O vínculo entre User e Militar é opcional para permitir contas da SME sem cadastro funcional de militar.



O relacionamento entre User e Militar utiliza a chave estrangeira `militares.user\_id`, que aceita valor nulo e possui restrição de unicidade.



## 4. Autenticação e autorização



A autenticação utiliza Laravel Sanctum com tokens Bearer.



### Principais operações de autenticação



| Método | Endpoint | Responsabilidade |

|---|---|---|

| POST | `/api/login` | Autentica o usuário e emite um token. |

| GET | `/api/me` | Retorna os dados do usuário autenticado. |

| POST | `/api/logout` | Revoga o token utilizado na requisição. |



### Perfis de acesso



\*\*SME\*\*



Responsável pela administração dos editais e pelo processo de análise das inscrições.



\*\*MILITAR\*\*



Responsável por consultar editais e realizar inscrições, além de consultar suas próprias inscrições.



\*\*SEM\_ACESSO\*\*



Perfil padrão atribuído a contas sem uma autorização funcional definida. Não permite acesso às operações protegidas por perfil.



### Controle de acesso



O middleware `PerfilMiddleware` centraliza a autorização por perfil.



A aplicação diferencia:



\- HTTP 401: usuário não autenticado.

\- HTTP 403: usuário autenticado, mas sem permissão para executar a operação.



Na consulta individual de inscrições, existe também uma verificação de propriedade: o militar pode consultar somente suas próprias inscrições, enquanto a SME pode consultar qualquer inscrição.



## 5. Fluxo de vida de um edital



Os editais possuem quatro situações:



| Situação | Descrição |

|---|---|

| RASCUNHO | Edital em elaboração. |

| ABERTO | Edital disponível para inscrições. |

| ENCERRADO | Período de inscrições finalizado. |

| EM\_ANALISE | Edital em processo de análise das inscrições. |



As alterações de situação são realizadas manualmente pela SME.



### Regras principais



\- O edital pode ser editado enquanto estiver em RASCUNHO.

\- O código do edital não pode ser alterado.

\- A edição do conteúdo é bloqueada quando já existem inscrições.

\- Para abrir um edital, é necessário possuir pelo menos um requisito e um destino.

\- As transições de situação respeitam a sequência do fluxo, sem retrocessos.



Fluxo esperado:



```text

RASCUNHO

&#x20;   |

&#x20;   v

&#x20;ABERTO

&#x20;   |

&#x20;   v

ENCERRADO

&#x20;   |

&#x20;   v

EM\_ANALISE

```



## 6. Fluxo de inscrição



O militar pode realizar uma inscrição somente em um edital ABERTO.



### Regras de criação



\- O aceite das condições é obrigatório.

\- É necessário selecionar pelo menos um destino.

\- Os destinos escolhidos devem pertencer ao edital informado na URL.

\- Não é permitido selecionar o mesmo destino mais de uma vez.

\- A quantidade de destinos não pode ultrapassar `max\_opcoes`.

\- Um militar não pode possuir mais de uma inscrição no mesmo edital.

\- O protocolo é gerado automaticamente pelo backend.

\- A ordem das escolhas é preservada.



### Criação das conferências



Após a criação da inscrição, o sistema gera automaticamente uma conferência para cada requisito do edital.



As conferências são criadas inicialmente com a situação `PENDENTE`.



A inscrição inicia com a situação `SUBMETIDA`.



## 7. Fluxo de análise da inscrição



A análise é realizada pela SME.



Para iniciar a análise individual:



\- O edital deve estar em `EM\_ANALISE`.

\- A inscrição deve estar em `SUBMETIDA`.



Ao iniciar, a inscrição passa para `EM\_ANALISE`.



### Atualização das conferências



A SME pode atualizar cada conferência para:



\- `ATENDE`

\- `NAO\_ATENDE`

\- `PENDENTE`



Quando uma conferência recebe `NAO\_ATENDE`, é obrigatório informar uma observação.



A observação possui limite de 1.000 caracteres.



Durante a análise, a SME pode retornar uma conferência para `PENDENTE`.



### Conclusão da análise



Não é permitido concluir uma inscrição enquanto existir alguma conferência pendente.



O resultado final é calculado pelo sistema:



| Condição | Resultado |

|---|---|

| Todas as conferências em ATENDE | APTA |

| Pelo menos uma conferência em NAO\_ATENDE | INAPTA |

| Existe alguma conferência PENDENTE | Análise não pode ser concluída |



## 8. Histórico e auditoria



O sistema registra eventos relevantes das operações, permitindo acompanhar as alterações realizadas.



Entre os eventos registrados estão:



\- `EDITAL\_CRIADO`

\- `CONTEUDO\_EDITAL\_ATUALIZADO`

\- `SITUACAO\_ALTERADA`

\- `INSCRICAO\_CRIADA`

\- `ANALISE\_INICIADA`

\- `CONFERENCIA\_ATUALIZADA`

\- `ANALISE\_CONCLUIDA`



Os registros de histórico são realizados de forma transacional junto às operações correspondentes, garantindo consistência entre as alterações de negócio e sua auditoria.



## 9. Respostas da API



Os Resources são utilizados para padronizar a representação das entidades nas respostas HTTP.



As respostas seguem o formato JSON definido pela aplicação, utilizando envelopes como `data` para os recursos retornados e mensagens para operações que precisam informar o resultado ao cliente.



Os códigos HTTP representam o resultado da operação, da autenticação, da autorização ou da validação dos dados recebidos.
