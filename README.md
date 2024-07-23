# WordPress 书签数据管理插件

## 说明

在 WordPress 中注册书签（Post Type）与分类，同时注册相关编辑器组件，可在 WordPress 后台编辑或管理书签及书签分类。

暂通过 admin-ajax 暴露公开接口，以此作为前端导航页面的数据源。

## 分支

插件仓库有两个分支。

### main 

旧版，提供基础功能（不再维护）

### third_order_plugin

拓展分支，在 main 的基础上，可以使用拖拽对书签和分类排序。

拓展分支依赖以下插件：

- `simple-custom-post-order`: 分类与书签列表页面拖拽排序

插件依赖已在插件中声明，使用 WordPress 6.5 及以上版本时，安装此插件将会自动安装依赖插件。

## 特点

大部分代码使用`ChatGPT`书写并由人工修正。

后台编辑页面可通过 WordPress 媒体选择器选择 ICON 图像，亦可填写三方 ICON url 链接。

# TODO

1. ~~通过 WP-REST API 提供接口服务（`/wp-json/wp/v2/`）~~)(已完成)
    - ~~自定义 Post 数据，添加 term 数据~~
    - ~~自定义分类数据，添加 term 数据与排序~~
