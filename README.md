# fm
php file manager

特点：解决中文gbk和utf-8编码问题，文件内容还有文件路径的编码。

办法：同时给出两种编码，由用户自己选择正确的编码

事项：文件内容，用上述办法，同时要浏览编辑时要转换含html字符。
事项：文件路径，windows用gbk，linux用utf8。
事项：文件路径，将utf8文件名传到windows，仍可用utf8访问，用gbk会变成其他汉字。
事项：文件路径，将gbk文件名传到linux，仍且只可用gbk访问,用utf8为乱码且无法识出。
事项：ftp软件在选项中可以设置，但上传下载仍可引起混乱，可能会误删另一个编码文件。

其他项目：
https://github.com/prasathmani/tinyfilemanager_
https://github.com/jcampbell1/simple-file-manager
https://github.com/alexantr/filemanager

http://phpfm.sourceforge.net
_
https://github.com/mustafa0x/pafm
https://github.com/eteplus/FileManager
https://github.com/misterunknown/ifm
https://github.com/caos30/php_srrFileManager
_
https://github.com/search?l=PHP&p=3&q=file+manager&type=Repositories&utf8=%E2%9C%93

<img src="https://raw.githubusercontent.com/webkb/fm/master/1.png" />
<img src="https://raw.githubusercontent.com/webkb/fm/master/2.png" />
