## [1.0.0-dev.4](https://github.com/laraxot/module_ai_fila5/compare/v1.0.0-dev.3...v1.0.0-dev.4) (2026-09-24)

### Features

* add ContextCompressorAction (moved from Xot) ([8e58211](https://github.com/laraxot/module_ai_fila5/commit/8e5821129e854f805ff2d600460162601848a8f8))
* **AI:** add Ollama actions moved from Modules/Xot ([54e9a2d](https://github.com/laraxot/module_ai_fila5/commit/54e9a2da918de114cc81c44d355e82e2150b960c))
* **tables:** aggiunge $model esplicito a XotBaseResourceTable subclasses (fase 1 audit) ([9df7309](https://github.com/laraxot/module_ai_fila5/commit/9df7309f12393363ee673392070cb969e04bc0de))
* **tables:** aggiunge $model esplicito a XotBaseResourceTable subclasses (fase 1 audit) ([da10edf](https://github.com/laraxot/module_ai_fila5/commit/da10edf82b558d55e961d116443e6f3657127778))

### Bug Fixes

* AI BaseModel missing $connection, matches sibling modules ([0e1a316](https://github.com/laraxot/module_ai_fila5/commit/0e1a316c7874fdd026ab5b8e86efc5b4f22cecb2))
* AI BaseModel missing $connection, matches sibling modules ([a61e844](https://github.com/laraxot/module_ai_fila5/commit/a61e844ccc2b455805b93c7e9268b5b72202049e))
* AI BaseModel/BasePivot loop correction, bmad story, docs/stories, second brain ([5c46c92](https://github.com/laraxot/module_ai_fila5/commit/5c46c9239be59a7d6ec466126fea89fbc45248d6))
* AI BaseModel/BasePivot loop correction, bmad story, docs/stories, second brain ([affe213](https://github.com/laraxot/module_ai_fila5/commit/affe2131f44e900c3d0437b8e80314f269c08df1))
* **AI:** add proper return type to getFormSchema() ([1ee9322](https://github.com/laraxot/module_ai_fila5/commit/1ee932221e17d81c2803edb1a1ef685e3eec32f9))
* **AI:** add proper return type to getFormSchema() ([630f1fd](https://github.com/laraxot/module_ai_fila5/commit/630f1fdd6ae2a981de456507a226f40969ba3268))
* **ai:** extend module BaseModel instead of XotBaseModel directly ([3d08380](https://github.com/laraxot/module_ai_fila5/commit/3d083805c1811b50dd385a084b2d9acca177a91d))
* **ai:** extend module BaseModel instead of XotBaseModel directly ([b0dccdc](https://github.com/laraxot/module_ai_fila5/commit/b0dccdcd6404c25589919803469d654b1955f37a))
* **AI:** phpmd cleanup + real OpenAI class_exists crash bug in ContextCompressorAction ([a9e15d3](https://github.com/laraxot/module_ai_fila5/commit/a9e15d3c0ddd51429c23d2a23f7efe8839756bf8))
* **AI:** phpmd cleanup + real OpenAI class_exists crash bug in ContextCompressorAction ([78d8c6d](https://github.com/laraxot/module_ai_fila5/commit/78d8c6d204274ab4cd5447e048403bb33a8fa0fa))
* **AI:** phpmd cleanup + real OpenAI class_exists crash bug in ContextCompressorAction ([f883621](https://github.com/laraxot/module_ai_fila5/commit/f883621ab83b3de41e26723151bc9850dccd30ae))
* **Ollama:** restore array-shape PHPDoc lost when Action moved from Xot ([316f6ba](https://github.com/laraxot/module_ai_fila5/commit/316f6ba6dc99fcc0cbe4c86549e076dbb16aca52))
* PHPStan AI missing BaseModel/BasePivot, bmad story, docs/stories, second brain ([3f2dd36](https://github.com/laraxot/module_ai_fila5/commit/3f2dd36e001ba7955bcb2bd4edc66e1e0cc666e1))
* PHPStan AI missing BaseModel/BasePivot, bmad story, docs/stories, second brain ([a5612a1](https://github.com/laraxot/module_ai_fila5/commit/a5612a187e5ad68557588126d476a8b0f490c0ae))
* PHPStan AI module, docs updated, second brain updated ([ea9e181](https://github.com/laraxot/module_ai_fila5/commit/ea9e181e350b1a36d8bed116403c7979ae4f2a5d))
* PHPStan AI module, docs updated, second brain updated ([0916c32](https://github.com/laraxot/module_ai_fila5/commit/0916c3251c0b7029b0269ae235d36180015ae9a6))
* PHPStan AI sync final, bmad story, docs/stories, second brain ([875d7f3](https://github.com/laraxot/module_ai_fila5/commit/875d7f3e6f2166aae400d1efd58fdd0b6d021291))
* PHPStan AI sync final, bmad story, docs/stories, second brain ([1a2f4bb](https://github.com/laraxot/module_ai_fila5/commit/1a2f4bb1c58110820d87edf49cc9c4a7f95dd717))
* PHPStan AI, bmad story, docs ([928b3fa](https://github.com/laraxot/module_ai_fila5/commit/928b3fa4c9504aff50620372cec88ffa69b4fc6b))
* PHPStan AI, bmad story, docs ([8bb3f7b](https://github.com/laraxot/module_ai_fila5/commit/8bb3f7b8f4224b038412fa517a172ef117db87b6))
* PHPStan AI, bmad story, docs/stories, second brain ([564dbd0](https://github.com/laraxot/module_ai_fila5/commit/564dbd0f4b4002c75b58e8e119d6b5bd8b8b1f05))
* PHPStan AI, bmad story, docs/stories, second brain ([a63ee4a](https://github.com/laraxot/module_ai_fila5/commit/a63ee4a28a0ad4fb1481cbb65b3005ddf552df0f))
* PHPStan AI, bmad story, docs/stories, second brain ([1dfe2ea](https://github.com/laraxot/module_ai_fila5/commit/1dfe2ea7bdab635b5150a843a908c8ed5e690ac1))
* PHPStan AI, bmad story, docs/stories, second brain ([b952650](https://github.com/laraxot/module_ai_fila5/commit/b952650885960d43a0b50167813c26db5964483e))
* PHPStan AI, bmad story, docs/stories, second brain ([657a8b1](https://github.com/laraxot/module_ai_fila5/commit/657a8b1b6308c6d9442ce344f7b1b66439bef551))
* PHPStan AI, bmad story, docs/stories, second brain ([cdf9bf3](https://github.com/laraxot/module_ai_fila5/commit/cdf9bf344c253ba1c7c430b6141a4b8c1d82e898))
* **phpstan:** analyse Modules a zero errori ([970cdd3](https://github.com/laraxot/module_ai_fila5/commit/970cdd3350b6dcdbc4e897790bd33d5b16a23100))
* **phpstan:** analyse Modules a zero errori ([5910045](https://github.com/laraxot/module_ai_fila5/commit/59100454691564234265af7d1da7ff6ab5cbbda7))
* **phpstan:** analyse Modules torna a zero dopo 73 ricadute (ROOT-17.10) ([e829031](https://github.com/laraxot/module_ai_fila5/commit/e829031721db3b803909889552c8d1f1b5d1093f)), closes [laraxot/base_quaeris_fila5#172](https://github.com/laraxot/base_quaeris_fila5/issues/172)
* **phpstan:** analyse Modules torna a zero dopo 73 ricadute (ROOT-17.10) ([ad5c138](https://github.com/laraxot/module_ai_fila5/commit/ad5c138d1152912412fb388cebb638ccb13e7b4d)), closes [laraxot/base_quaeris_fila5#172](https://github.com/laraxot/base_quaeris_fila5/issues/172)
* **phpstan:** analyse Modules torna a zero dopo 73 ricadute (ROOT-17.10) ([10a4772](https://github.com/laraxot/module_ai_fila5/commit/10a47720f841f1a076c69b86e32ec1e81da68e09)), closes [laraxot/base_quaeris_fila5#172](https://github.com/laraxot/base_quaeris_fila5/issues/172)

# Changelog

Tutte le modifiche rilevanti di questo pacchetto sono documentate in questo file.

Il file viene aggiornato automaticamente da [semantic-release](https://github.com/semantic-release/semantic-release) (Conventional Commits).
