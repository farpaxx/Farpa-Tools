/**
 * Sistema simples de tradução para múltiplos idiomas
 */
class Translator {
    constructor() {
        this.currentLanguage = 'pt-br';
        this.translations = {
            'pt-br': {},
            'en': {},
            'es': {}
        };
        this.elementsToTranslate = [];
    }

    // Adicionar traduções para uma chave específica
    addTranslation(key, translations) {
        for (const lang in translations) {
            if (this.translations[lang]) {
                this.translations[lang][key] = translations[lang];
            }
        }
    }

    // Adicionar múltiplas traduções de uma vez
    addTranslations(translationsObject) {
        for (const key in translationsObject) {
            this.addTranslation(key, translationsObject[key]);
        }
    }

    // Registrar elementos HTML para tradução
    registerElement(element, translationKey) {
        const originalText = element.innerHTML;
        this.elementsToTranslate.push({
            element: element,
            key: translationKey,
            original: originalText
        });
    }

    // Registrar todos os elementos com o atributo data-translate
    registerAllElements() {
        const elements = document.querySelectorAll('[data-translate]');
        elements.forEach(element => {
            const key = element.getAttribute('data-translate');
            this.registerElement(element, key);
        });
    }

    // Mudar o idioma atual e atualizar todos os elementos
    changeLanguage(language) {
        if (this.translations[language]) {
            this.currentLanguage = language;
            this.updateAllElements();
            localStorage.setItem('preferredLanguage', language);
            
            // Atualizar classe do body para facilitar estilização por idioma
            document.body.classList.remove('lang-pt-br', 'lang-en', 'lang-es');
            document.body.classList.add('lang-' + language);
            
            // Atualizar botões de idioma ativos
            document.querySelectorAll('.language-selector button').forEach(button => {
                if (button.getAttribute('data-lang') === language) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
        }
    }

    // Atualizar o texto de todos os elementos registrados
    updateAllElements() {
        // Traduzir elementos com data-translate
        this.elementsToTranslate.forEach(item => {
            const translation = this.translations[this.currentLanguage][item.key];
            if (translation) {
                // Verificar se o elemento tem filhos com data-translate
                if (item.element.querySelectorAll('[data-translate]').length === 0) {
                    item.element.innerHTML = translation;
                }
            } else {
                // Fallback para o texto original se não houver tradução
                if (item.element.querySelectorAll('[data-translate]').length === 0) {
                    item.element.innerHTML = item.original;
                }
            }
        });
        
        // Traduzir placeholders
        document.querySelectorAll('[data-translate-placeholder]').forEach(element => {
            const key = element.getAttribute('data-translate-placeholder');
            const translation = this.translations[this.currentLanguage][key];
            if (translation) {
                element.placeholder = translation;
            }
        });
        
        // Traduzir títulos (title attribute)
        document.querySelectorAll('[data-translate-title]').forEach(element => {
            const key = element.getAttribute('data-translate-title');
            const translation = this.translations[this.currentLanguage][key];
            if (translation) {
                element.title = translation;
            }
        });
    }

    // Inicializar o tradutor
    initialize() {
        // Verificar se há um idioma preferido no localStorage
        const savedLanguage = localStorage.getItem('preferredLanguage');
        if (savedLanguage && this.translations[savedLanguage]) {
            this.currentLanguage = savedLanguage;
        } else {
            // Garantir que o padrão seja português brasileiro
            this.currentLanguage = 'pt-br';
            
            // Apenas para detecção opcional do idioma do navegador
            // const browserLang = navigator.language.substring(0, 2);
            // if (this.translations[browserLang]) {
            //     this.currentLanguage = browserLang;
            // }
        }

        this.registerAllElements();
        this.updateAllElements();

        // Adicionar classe de idioma ao body
        document.body.classList.add('lang-' + this.currentLanguage);
        
        // Atualizar botão ativo
        const currentLangButton = document.querySelector('.language-selector button[data-lang="' + this.currentLanguage + '"]');
        if (currentLangButton) {
            currentLangButton.classList.add('active');
        }
    }
}

// Criar instância global
const translator = new Translator();

// Carregar as traduções básicas
translator.addTranslations({
    'title': {
        'pt-br': 'Gerenciador de Itens',
        'en': 'Item Manager',
        'es': 'Gestor de Artículos'
    },
    'items': {
        'pt-br': 'Itens',
        'en': 'Items',
        'es': 'Artículos'
    },
    'search': {
        'pt-br': 'Buscar...',
        'en': 'Search...',
        'es': 'Buscar...'
    },
    'process-image': {
        'pt-br': 'Processar Imagem',
        'en': 'Process Image',
        'es': 'Procesar Imagen'
    },
    'add-new': {
        'pt-br': 'Adicionar Novo',
        'en': 'Add New',
        'es': 'Añadir Nuevo'
    },
    'edit': {
        'pt-br': 'Editar',
        'en': 'Edit',
        'es': 'Editar'
    },
    'delete': {
        'pt-br': 'Excluir',
        'en': 'Delete',
        'es': 'Eliminar'
    },
    'settings': {
        'pt-br': 'Configurações',
        'en': 'Settings',
        'es': 'Configuración'
    },
    'bg-removal': {
        'pt-br': 'Remoção de Fundo',
        'en': 'Background Removal',
        'es': 'Eliminación de Fondo'
    },
    'smoothness': {
        'pt-br': 'Nível de Suavização',
        'en': 'Smoothness Level',
        'es': 'Nivel de Suavizado'
    },
    'method': {
        'pt-br': 'Método',
        'en': 'Method',
        'es': 'Método'
    },
    'sharp-edges': {
        'pt-br': 'Bordas nítidas',
        'en': 'Sharp edges',
        'es': 'Bordes nítidos'
    },
    'moderate': {
        'pt-br': 'Suavização moderada',
        'en': 'Moderate smoothing',
        'es': 'Suavizado moderado'
    },
    'high-smooth': {
        'pt-br': 'Suavização alta',
        'en': 'High smoothing',
        'es': 'Suavizado alto'
    },
    'tip': {
        'pt-br': 'Dica',
        'en': 'Tip',
        'es': 'Consejo'
    },
    'removebg': {
        'pt-br': 'IA avançada',
        'en': 'Advanced AI',
        'es': 'IA avanzada'
    },
    'save': {
        'pt-br': 'Salvar',
        'en': 'Save',
        'es': 'Guardar'
    },
    'cancel': {
        'pt-br': 'Cancelar',
        'en': 'Cancel',
        'es': 'Cancelar'
    },
    'download': {
        'pt-br': 'Baixar',
        'en': 'Download',
        'es': 'Descargar'
    },
    'upload': {
        'pt-br': 'Enviar',
        'en': 'Upload',
        'es': 'Subir'
    },
    'preview': {
        'pt-br': 'Visualizar',
        'en': 'Preview',
        'es': 'Vista previa'
    },
    'lista-items': {
        'pt-br': 'Lista Items',
        'en': 'Items List',
        'es': 'Lista de Artículos'
    },
    'configuracoes': {
        'pt-br': 'Configurações',
        'en': 'Settings',
        'es': 'Configuración'
    },
    'caminho-imagens': {
        'pt-br': 'Caminho das Imagens:',
        'en': 'Images Path:',
        'es': 'Ruta de Imágenes:'
    },
    'salvar-configuracoes': {
        'pt-br': 'Salvar Configurações',
        'en': 'Save Settings',
        'es': 'Guardar Configuración'
    },
    'configure-env': {
        'pt-br': 'Configurar Arquivo .env Completo',
        'en': 'Configure Complete .env File',
        'es': 'Configurar Archivo .env Completo'
    },
    'atencao': {
        'pt-br': 'Atenção!',
        'en': 'Attention!',
        'es': '¡Atención!'
    },
    'item': {
        'pt-br': 'Item',
        'en': 'Item',
        'es': 'Artículo'
    },
    'label': {
        'pt-br': 'Label',
        'en': 'Label',
        'es': 'Etiqueta'
    },
    'limite': {
        'pt-br': 'Limite',
        'en': 'Limit',
        'es': 'Límite'
    },
    'acoes': {
        'pt-br': 'Ações',
        'en': 'Actions',
        'es': 'Acciones'
    },
    'modificar': {
        'pt-br': 'Modificar',
        'en': 'Modify',
        'es': 'Modificar'
    },
    'imagem': {
        'pt-br': 'Imagem',
        'en': 'Image',
        'es': 'Imagen'
    },
    'configuracoes-tab': {
        'pt-br': 'Configurações',
        'en': 'Settings',
        'es': 'Configuración'
    },
    'sem-imagem': {
        'pt-br': 'sem imagem',
        'en': 'no image',
        'es': 'sin imagen'
    },
    'primeiro-item': {
        'pt-br': 'Primeiro item sem imagem',
        'en': 'First item without image',
        'es': 'Primer artículo sin imagen'
    },
    'anterior': {
        'pt-br': 'Anterior',
        'en': 'Previous',
        'es': 'Anterior'
    },
    'proximo': {
        'pt-br': 'Próximo',
        'en': 'Next',
        'es': 'Siguiente'
    },
    'ultimo-item': {
        'pt-br': 'Último item sem imagem',
        'en': 'Last item without image',
        'es': 'Último artículo sin imagen'
    },
    'verificar-caminho': {
        'pt-br': 'Verifique se o caminho das imagens está correto. Sugestão:',
        'en': 'Check if the image path is correct. Suggestion:',
        'es': 'Verifica si la ruta de imágenes es correcta. Sugerencia:'
    },
    'baixar': {
        'pt-br': 'Baixar',
        'en': 'Download',
        'es': 'Descargar'
    },
    'processar-imagem': {
        'pt-br': 'Processar Imagem',
        'en': 'Process Image',
        'es': 'Procesar Imagen'
    },
    'config-tab': {
        'pt-br': 'Configurações',
        'en': 'Settings',
        'es': 'Configuración'
    },
    'can': {
        'pt-br': 'Can',
        'en': 'Can',
        'es': 'Puede'
    },
    'remove': {
        'pt-br': 'Remove',
        'en': 'Remove',
        'es': 'Remover'
    },
    'type': {
        'pt-br': 'Type',
        'en': 'Type',
        'es': 'Tipo'
    },
    'usavel': {
        'pt-br': 'Usável',
        'en': 'Usable',
        'es': 'Utilizable'
    },
    'descricao': {
        'pt-br': 'Descrição',
        'en': 'Description',
        'es': 'Descripción'
    },
    'imagem-col': {
        'pt-br': 'Imagem',
        'en': 'Image',
        'es': 'Imagen'
    },
    'baixar-btn': {
        'pt-br': 'Baixar',
        'en': 'Download',
        'es': 'Descargar'
    },
    'configuracoes-titulo': {
        'pt-br': 'Configurações',
        'en': 'Settings',
        'es': 'Configuración'
    }
});

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    translator.initialize();
    
    // Adicionar listeners para botões de idioma
    document.querySelectorAll('.language-selector button').forEach(button => {
        button.addEventListener('click', function() {
            translator.changeLanguage(this.getAttribute('data-lang'));
        });
    });
}); 