# WordPress默认数据导入到Fastadmin-cms中
简介（Ajax版）
Author：xslooi

## 使用说明
1. 配置导出数据库连接和配置导入数据库连接
2. 注意先导入栏目数据
3. 重置加载并配置导出栏目id
4. 重置加载并配置导入栏目id
5. 系统提示模型字段是否匹配（手动在后台添加模型字段）
6. 一次一条，进度条提示完成
7. 再勾选“清除占位的useless数据”，删除无效数据（织梦被删数据默认useless处理）
8. 可以勾选“文章id新增导入”（可以先导入同步旧数据再新增其它数据，不可反向，导入旧数据会更新同步id内容）

* **改版：系统默认文章id与老系统数据一致（即不影响SEO已收录的文章链接）**
* 附加表或自定义字段需要在新系统中数据库字段名称全匹配（可以使模板替换标签直接调用——使用字段映射会增加系统复杂度）；
* 一次导入一个栏目使用更加灵活可以只导入新闻部分等（注意小心重复导入）。

### TODO 此工具代码仅为实现功能硬编码（未重构和模块化）但内部基本逻辑和方法稍加修改即可替换成各种CMS系统数据和模板的转换工具
