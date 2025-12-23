# RedisTools
This set of tools is not designed to debug real world scenario with millions entries.
In fact, it is a simple development tool to preview PSR-6 pools or PSR-16 Simple Cache while set up with, for instance, some fixtures.

![redis tools screenshot terminal preview](./redis-tools.png)


## Note:
This tool primary purpose is to pair with my own implementation of PSR-6 pools or PSR-16 Simple Cache:
![RedisCache by Laurent LEGAZ](https://github.com/llegaz/RedisCache/workflows/CI/badge.svg)](https://github.com/llegaz/RedisCache/actions)

I don't think there will be any units here or at least for now.
Reconsider this if we enhance the Tools to be usable in production (custom Lua batch scripts ? excluding O(n) complexity (HGETALL)).

**See you space cowboy...** 🚀
