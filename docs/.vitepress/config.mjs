import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Lartrix",
  description: "Laravel 后台管理包 - PHP Schema Builder",
  lastUpdated: true,

  locales: {
    root: {
      label: '简体中文',
      lang: 'zh-CN'
    },
    en: {
      label: 'English',
      lang: 'en-US',
      link: '/en/',
      themeConfig: {
        nav: [
          { text: 'Home', link: '/en/' },
          { text: 'Guide', link: '/en/guide/' },
          { text: 'API Reference', link: '/en/api/' },
          { text: 'Examples', link: '/en/examples/' },
        ],
        sidebar: {
          '/en/guide/': [
            {
              text: 'Getting Started',
              items: [
                { text: 'Introduction', link: '/en/guide/' },
                { text: 'Installation', link: '/en/guide/installation' },
                { text: 'Quick Start', link: '/en/guide/quickstart' },
                { text: 'Configuration', link: '/en/guide/configuration' },
              ]
            },
            {
              text: 'Core Concepts',
              items: [
                { text: 'Architecture Overview', link: '/en/guide/architecture' },
                { text: 'Schema System', link: '/en/guide/schema' },
                { text: 'CrudController', link: '/en/guide/crud-controller' },
                { text: 'Permission System', link: '/en/guide/permissions' },
              ]
            },
            {
              text: 'Schema Components',
              items: [
                { text: 'Component Overview', link: '/en/guide/components/' },
                { text: 'Basic Components', link: '/en/guide/components/basic' },
                { text: 'Form Components', link: '/en/guide/components/form' },
                { text: 'Data Display', link: '/en/guide/components/data' },
                { text: 'Layout Components', link: '/en/guide/components/layout' },
                { text: 'Feedback Components', link: '/en/guide/components/feedback' },
                { text: 'Business Components', link: '/en/guide/components/business' },
              ]
            },
            {
              text: 'Actions',
              items: [
                { text: 'Action Overview', link: '/en/guide/actions/' },
                { text: 'SetAction', link: '/en/guide/actions/set' },
                { text: 'CallAction', link: '/en/guide/actions/call' },
                { text: 'FetchAction', link: '/en/guide/actions/fetch' },
                { text: 'IfAction', link: '/en/guide/actions/if' },
                { text: 'Other Actions', link: '/en/guide/actions/others' },
              ]
            },
            {
              text: 'Advanced',
              items: [
                { text: 'Modular Development', link: '/en/guide/modules' },
                { text: 'Sub-Admin Systems', link: '/en/guide/sub-admin' },
                { text: 'Data Dictionary', link: '/en/guide/dict' },
                { text: 'Notification System', link: '/en/guide/notifications' },
                { text: 'Custom Components', link: '/en/guide/custom-components' },
              ]
            }
          ],
          '/en/api/': [
            {
              text: 'API Reference',
              items: [
                { text: 'Overview', link: '/en/api/' },
                { text: 'Authentication API', link: '/en/api/auth' },
                { text: 'User Management', link: '/en/api/users' },
                { text: 'Role Management', link: '/en/api/roles' },
                { text: 'Permission Management', link: '/en/api/permissions' },
                { text: 'Menu Management', link: '/en/api/menus' },
                { text: 'Module Management', link: '/en/api/modules' },
                { text: 'System Settings', link: '/en/api/settings' },
                { text: 'Data Dictionary', link: '/en/api/dict' },
                { text: 'Notification Management', link: '/en/api/notifications' },
              ]
            },
            {
              text: 'PHP Class Reference',
              items: [
                { text: 'Controllers', link: '/en/api/php/controllers' },
                { text: 'Schema Components', link: '/en/api/php/schema-components' },
                { text: 'Actions', link: '/en/api/php/actions' },
                { text: 'Services', link: '/en/api/php/services' },
                { text: 'Models', link: '/en/api/php/models' },
              ]
            }
          ],
          '/en/examples/': [
            {
              text: 'Examples',
              items: [
                { text: 'Overview', link: '/en/examples/' },
                { text: 'User Management', link: '/en/examples/users' },
                { text: 'Post Management', link: '/en/examples/posts' },
                { text: 'Product Management', link: '/en/examples/products' },
                { text: 'Category Management', link: '/en/examples/categories' },
                { text: 'Full Module Example', link: '/en/examples/full-module' },
              ]
            }
          ]
        },
        editLink: {
          pattern: 'https://github.com/your-org/lartrix/edit/main/docs/:path',
          text: 'Edit this page on GitHub'
        },
        docFooter: {
          prev: 'Previous',
          next: 'Next'
        },
        outline: {
          label: 'On this page'
        },
        lastUpdated: {
          text: 'Last updated'
        }
      }
    }
  },

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    logo: '/logo.svg',
    nav: [
      { text: '首页', link: '/' },
      { text: '指南', link: '/guide/' },
      { text: 'API 参考', link: '/api/' },
      { text: '示例', link: '/examples/' },
    ],

    sidebar: {
      '/guide/': [
        {
          text: '开始',
          items: [
            { text: '介绍', link: '/guide/' },
            { text: '安装', link: '/guide/installation' },
            { text: '快速开始', link: '/guide/quickstart' },
            { text: '配置', link: '/guide/configuration' },
          ]
        },
        {
          text: '核心概念',
          items: [
            { text: '架构概览', link: '/guide/architecture' },
            { text: 'Schema 系统', link: '/guide/schema' },
            { text: 'CrudController', link: '/guide/crud-controller' },
            { text: '权限系统', link: '/guide/permissions' },
          ]
        },
        {
          text: 'Schema 组件',
          items: [
            { text: '组件概述', link: '/guide/components/' },
            { text: '基础组件', link: '/guide/components/basic' },
            { text: '表单组件', link: '/guide/components/form' },
            { text: '数据展示', link: '/guide/components/data' },
            { text: '布局组件', link: '/guide/components/layout' },
            { text: '反馈组件', link: '/guide/components/feedback' },
            { text: '业务组件', link: '/guide/components/business' },
          ]
        },
        {
          text: 'Actions',
          items: [
            { text: 'Action 概述', link: '/guide/actions/' },
            { text: 'SetAction', link: '/guide/actions/set' },
            { text: 'CallAction', link: '/guide/actions/call' },
            { text: 'FetchAction', link: '/guide/actions/fetch' },
            { text: 'IfAction', link: '/guide/actions/if' },
            { text: '其他 Actions', link: '/guide/actions/others' },
          ]
        },
        {
          text: '进阶',
          items: [
            { text: '模块化开发', link: '/guide/modules' },
            { text: '二级后台', link: '/guide/sub-admin' },
            { text: '数据字典', link: '/guide/dict' },
            { text: '通知系统', link: '/guide/notifications' },
            { text: '自定义组件', link: '/guide/custom-components' },
          ]
        }
      ],
      '/api/': [
        {
          text: 'API 参考',
          items: [
            { text: '概述', link: '/api/' },
            { text: '认证 API', link: '/api/auth' },
            { text: '用户管理', link: '/api/users' },
            { text: '角色管理', link: '/api/roles' },
            { text: '权限管理', link: '/api/permissions' },
            { text: '菜单管理', link: '/api/menus' },
            { text: '模块管理', link: '/api/modules' },
            { text: '系统设置', link: '/api/settings' },
            { text: '数据字典', link: '/api/dict' },
            { text: '通知管理', link: '/api/notifications' },
          ]
        },
        {
          text: 'PHP 类参考',
          items: [
            { text: 'Controllers', link: '/api/php/controllers' },
            { text: 'Schema 组件', link: '/api/php/schema-components' },
            { text: 'Actions', link: '/api/php/actions' },
            { text: 'Services', link: '/api/php/services' },
            { text: 'Models', link: '/api/php/models' },
          ]
        }
      ],
      '/examples/': [
        {
          text: '示例',
          items: [
            { text: '概述', link: '/examples/' },
            { text: '用户管理', link: '/examples/users' },
            { text: '文章管理', link: '/examples/posts' },
            { text: '商品管理', link: '/examples/products' },
            { text: '分类管理', link: '/examples/categories' },
            { text: '完整模块示例', link: '/examples/full-module' },
          ]
        }
      ]
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/your-org/lartrix' }
    ],

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/your-org/lartrix/edit/main/docs/:path',
      text: '在 GitHub 上编辑此页'
    },

    docFooter: {
      prev: '上一页',
      next: '下一页'
    },

    outline: {
      label: '页面导航'
    },

    lastUpdated: {
      text: '最后更新于'
    },

    footer: {
      message: '基于 MIT 许可发布',
      copyright: 'Copyright © 2024-present Lartrix Team'
    }
  }
})