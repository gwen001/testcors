# TestCors
PHP tool to test Cross Origin Resource Sharing aka CORS.  
Note that this is an automated tool, manual check is still required.  

```
Usage: php testcors.php [OPTIONS] -o <host>

Options:
	-h	print this help
	-o	single host to test or source file
	-r	do not follow redirection
	-s	force https

Examples:
	php testcors.php -o www.example.com
	php testcors.php -r -s -o domains.txt
```

I don't believe in license.  
You can do want you want with this program.  
