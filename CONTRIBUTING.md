# Contributing to the HiPay Enterprise module for PrestaShop 1.6.x - 1.7.x

Contributions to the HiPay Enterprise module for PrestaShop 1.6.x - 1.7.x should be made via GitHub [pull
requests][pull-requests] and discussed using
GitHub [issues][issues].

All contributions - even if we take them as the original author in the end - will be closed.

GitHub is not our main system and we simply allow you to read the code and work for us, without any counterparties; not even the joy of the open-source contribution.

### Before you start

If you would like to make a significant change, please open
an issue to discuss it, in order to minimize duplication of effort.

### Development
Installation with Docker for testing

If you are a developer or a QA developer, you can use this project with Docker and Docker Compose.
Requirements for your environment:
- Git (https://git-scm.com/)
- Docker (https://docs.docker.com/engine/installation/)
- Docker Compose (https://docs.docker.com/compose/)

Here is the procedure to be applied to a Linux environment:

Open a terminal and select the folder of your choice.

Clone the HiPay Enterprise PrestaShop project in your environment with Git:


```
$ git clone https://github.com/hipay/hipay-enterprise-sdk-prestashop.git
```

Copy the content from the file `bin/docker/conf/development/env.sample` and paste it in `bin/docker/conf/development/env` file.
Then, fill it with your personal vars.

Go in the project root folder and enter this command:

```
$ ./prestashop.sh init
```

Your container is loading: wait for a few seconds while Docker installs PrestaShop and the HiPay module.*

You can now test the HiPay Enterprise module in a browser with this URL:

 - http://localhost:8086 (PRESTASHOP 16)
 - http://localhost:8087 (PRESTASHOP 17)

To connect to the back office, go to this URL:

 - http://localhost:8086/admin-hipay (PRESTASHOP 16)
 - http://localhost:8087/admin-hipay (PRESTASHOP 17)

The login and password are demo@hipay.com / hipay123.
You can test the module with your account configuration.

### Making the request

Development takes place against the `develop` branch of this repository and pull
requests should be opened against that branch.

### Testing

Functional tests are implemented and cover the main functionalities offered by the module.
Before you make your pull request, make sure to run them and make sure they are all sucess.
 
The tests were developed in CasperJS and PhantomJS.
The CasperJS tests are located in / bin / tests

In order to run the tests, you must install CasperJS and PhantomJS.

Please refer to the installation instructions on the page [CasperJS](http://docs.casperjs.org/en/latest/installation.html)
  

Then to run the tests, run:
  
  -  Prestashop 1.7
  ```
  sudo ./prestashop.sh test 17
  ```
  
  -  Prestashop 1.6
  ```
  sudo ./prestashop.sh test 16
  ```
 

## Licensing

The HiPay Enterprise module for PrestaShop 1.6.x - 1.7.x is released under an [Apache
2.0][project-license] license. Any code you submit will be
released under that license.

[project-license]: LICENSE.md

[pull-requests]: https://github.com/hipay/hipay-enterprise-sdk-prestashop/pulls
[issues]: https://github.com/hipay/hipay-enterprise-sdk-prestashop/issues
