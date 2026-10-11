# Security Policy

## Supported versions

Security fixes are made to the latest release only.

## Reporting a vulnerability

Please do not open a public issue for a security problem. Email
[scott@cariss.dev](mailto:scott@cariss.dev) with a description of the problem
and, if you can, steps to reproduce it. You will get a reply as soon as
possible, and a fix will be released before the details are made public.

This project is a single WordPress drop-in file that stores data in APCu on the
same server. Problems we would like to hear about include ways to read or
overwrite another site's cached data, ways to make the cache serve data that
was flushed, and unescaped output.
