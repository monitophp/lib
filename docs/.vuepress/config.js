module.exports = {
  title: 'MonitoLib',
  description: 'Biblioteca em PHP para desenvolvimento de APIs',
  port: 8091,
  themeConfig: {
    sidebar: {
      '/guide/': [
        {
          title: 'Introdução',
          path: '/guide/definicoes'
        },
        {
          title: 'Controllers',
          path: '/guide/controllers',
          collapsable: true
        },
        {
          title: 'Rotas',
          path: '/guide/rotas'
        },
        {
          title: 'Banco de dados',
          path: '/guide/database',
          collapsable: true
        },
        {
          title: 'API',
          path: '/guide/src',
          collapsable: false,
          children: [
            {
              title: 'App',
              path: '/guide/src/app',
              collapsable: true
            },
            {
              title: 'Dev',
              path: '/guide/src/dev',
              collapsable: true
            }
           ]
        },
        {
          title: 'Release notes',
          path: '/guide/release',
          collapsable: true
        }
        // {
        //   title: 'API',
        //   path: '/guide/api/dev'
        // },
        // {
        //   title: 'PHP',
        //   path: '/guide/php/',
        //   collapsable: false,
        //   children: [
        //     {
        //       title: 'Installation',
        //       path: '/guide/installation/',
        //       collapsable: true
        //     },
        //     {
        //       title: 'Getting Started',
        //       path: '/guide/',
        //       collapsable: true
        //     },
        //     {
        //       title: 'Validation',
        //       path: '/guide/validation/',
        //       collapsable: true
        //     },
        //     {
        //       title: 'Custom Inputs',
        //       path: '/guide/custom-inputs/',
        //       collapsable: true
        //     },
        //     {
        //       title: 'Plugins',
        //       path: '/guide/plugins/',
        //       collapsable: true
        //     },
        //     {
        //       title: 'Theming',
        //       path: '/guide/theming/',
        //       collapsable: true
        //     }
        //   ]
        // },
        // {
        //   title: 'MySQL',
        //   collapsable: false,
        //   path: '/guide/mysql/',
        //   children: [
        //     {
        //       title: 'Configuration',
        //       collapsable: true,
        //       path: '/guide/inputs/',
        //     },
        //     ...[
        //       '/guide/inputs/text/',
        //       '/guide/inputs/box/',
        //       '/guide/inputs/button/',
        //       '/guide/inputs/file/',
        //       '/guide/inputs/select/',
        //       '/guide/inputs/sliders/',
        //       '/guide/inputs/textarea/'
        //     ]
        //   ]
        // },
        // {
        //   title: 'Oracle',
        //   collapsable: false,
        //   path: '/guide/forms',
        //   children: [
        //     {
        //       title: 'Using forms',
        //       collapsable: true,
        //       path: '/guide/forms/',
        //     }
        //   ]
        // }
      ]
    }
  }
}