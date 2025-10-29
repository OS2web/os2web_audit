# Implementations

The following contrib modules inject an audit logger.

## OS2Forms submodules

Modules within [os2forms/os2forms](https://github.com/OS2Forms/os2forms) that audit logs:

### [os2forms_fbs_handler](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_fbs_handler)

Audit logs when:

* Patron is created

### [os2forms_digital_post](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_digital_post)

Audit logs when:

* Digital post is sent

### [os2forms_fasit](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_fasit)

Audit logs when:

* File is uploaded to Fasit
* Document is uploaded to Fasit

### [os2forms_nemid](https://github.com/OS2Forms/os2forms/tree/develop/modules/os2forms_nemid)

Audit logs when:

* User is looking up CPR-number.

## Other modules

### [os2web/os2web_datalookup](https://github.com/OS2web/os2web_datalookup)

Audit logs when:

* Company is queried via CVR-number
* Company is queried via P-number
* Person is queried via CPR-number

### [itk-dev/os2forms_nemlogin_openid_connect](https://github.com/itk-dev/os2forms_nemlogin_openid_connect)

Audit logs when:

* Upon OIDC login
* Upon OIDC logout

### [os2forms/os2forms_get_organized](https://github.com/OS2Forms/os2forms_get_organized)

Audit logs when:

* Case is created
* Relation is established between documents
* Documents are finalized (journaliseret)
* Document is archived to case

### [os2forms/os2forms_organisation](https://github.com/itk-dev/os2forms_organisation)

Audit logs when:

* User is looking up organisation data.
