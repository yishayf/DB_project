import re
str = "Cyclists at the 1984 Summer Olympics"
p = re.compile('(.*) at the (\d{4}) (summer|winter) Olympics', re.IGNORECASE)
m = p.match(str)
print m.group(4)
