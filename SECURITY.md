# Security Policy

## Supported versions

Security fixes are made to the latest release only.

## Reporting a vulnerability

Please do not open a public issue for a security problem. Report it privately
on GitHub: open the repository's **Security** tab and choose **Report a
vulnerability**. If you cannot use that, email
[scott@cariss.dev](mailto:scott@cariss.dev). Either way, describe the problem
and, if you can, how to reproduce it. You will get a reply as soon as
possible, and a fix will be released before the details are made public.

This project is a single WordPress drop-in file that stores data in APCu on the
same server. Problems we would like to hear about include ways to read or
overwrite another site's cached data, ways to make the cache serve data that
was flushed, and unescaped output.
