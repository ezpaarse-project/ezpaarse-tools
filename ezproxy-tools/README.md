# EZproxy tools

This repository is intended to recived useful tools for interactions between ezPAARSE and EZproxy

Use : 
 - **add_domains_from_ezproxyconfig.sh** to enrich an ezpaarse platform manifest.json with domains taken from an ezproxy config.txt file.  
This program assume that ezproxy **config.txt** file use **DJ directives** to describe the proxified domains and add them to the manifest if they doesn't exists

Example :
```bash
add_domains_from_ezproxyconfig.sh samples/ovid_ezproxy_config.txt samples/ovid_manifest.json > new_ovid_manifest.json
```
You can output this command to a new *manifest.json* for this platform and if it's enriched submit a pull request to update the platform.



