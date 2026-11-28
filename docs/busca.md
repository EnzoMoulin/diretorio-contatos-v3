# Busca por sinônimos: as 39 consultas medidas

Contagem de linhas por consulta, com `limite` desligado, no seed da v2 (1.807 contatos, 856 nós,
73 locais, 69 comarcas, 11 úteis) em 25/11/2026. "Antes" é a mesma busca com a expansão desligada — é o número
que o usuário veria hoje; "Depois" é com o léxico. Quando "antes" é maior que "depois", o trigrama
estava devolvendo letra parecida, não o serviço.

| Termo digitado | Expansão | Antes | Depois |
| :--- | :--- | ---: | ---: |
| `reconhecimento de firma` | `cartorio` | 0 | **154** |
| `firma reconhecida` | `cartorio` | 0 | **154** |
| `tabelionato` | `cartorio` | 0 | **154** |
| `escritura publica` | `cartorio` | 0 | **154** |
| `registro de imoveis` | `registro` | 0 | **18** |
| `certidao de nascimento` | `registro` | 0 | **18** |
| `pensao alimenticia` | `familia` | 0 | **42** |
| `divorcio` | `familia` | 0 | **42** |
| `guarda de filho` | `familia` | 0 | **42** |
| `heranca` | `sucessoes` | 13 | **11** |
| `inventario` | `sucessoes` | 0 | **11** |
| `testamento` | `sucessoes` | 56 | **11** |
| `tutela de menor` | `orfaos` | 0 | **15** |
| `conselho tutelar` | `infancia juventude` | 0 | **28** |
| `crianca e adolescente` | `infancia juventude` | 0 | **28** |
| `menor infrator` | `socioeducativo` | 0 | **1** |
| `justica do trabalho` | `trabalho` | 0 | **9** |
| `processo do trabalho` | `trabalho` | 0 | **9** |
| `inss` | `previdencia` | 98 | **8** |
| `aposentadoria` | `previdencia` | 73 | **8** |
| `pericia medica` | `previdencia` | 0 | **8** |
| `auxilio doenca` | `previdencia` | 0 | **8** |
| `intimacao` | `mandados` | 0 | **28** |
| `oficial de justica` | `oficiais de justica` | 37 | **37** |
| `marcar audiencia` | `audiencias` | 0 | **35** |
| `pequenas causas` | `juizado` | 0 | **148** |
| `juizado de pequenas causas` | `juizado` | 0 | **148** |
| `medida protetiva` | `violencia mulher` | 0 | **8** |
| `crime` | `criminal` | 96 | **131** |
| `juri popular` | `juri` | 0 | **23** |
| `tribunal do juri` | `juri` | 2 | **23** |
| `divida ativa` | `execucao fiscal` | 0 | **1** |
| `divida` | `execucao` | 1 | **15** |
| `iptu` | `fazenda` | 0 | **23** |
| `tributo` | `fazenda` | 4 | **23** |
| `balcao` | `atendimento` | 0 | **119** |
| `atendimento ao publico` | `atendimento` | 13 | **119** |
| `plantao judiciario` | `plantao` | 10 | **27** |
| `urgencia` | `plantao` | 0 | **27** |

A medição é reproduzível: `SinonimoSeeder::LEXICO` percorrido com `Buscar::executar($termo, 0, false)`
e `Buscar::executar($termo, 0, true)`. O critério de aceite está descrito no
[README](../README.md#busca-por-sinônimos-ideia-04).
