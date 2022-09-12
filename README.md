# Survey eSignature Certification
********************************************************************************
Collect certified electronic signatures with respective logging and custom attestation language.

### Overview
********************************************************************************
The Survey eSignature Certification External Module (EM) provides means for collecting a certified electronic signature by
collecting the survey recipient identifier and displaying a customized attestation language. In its basic form, a REDCap
Admin can specify the institution's approved language for electronic signature attestation, which will be available
on all projects onto which this module is enabled. The module is compatible with MLM. Additionally, the module can also generate and store
PDF files of the certification process. It provides server side processing of large files (to avoid timing issues on the browser),
and storage of these files in the project's File Repository.

### Getting Started - Basic Setup:
********************************************************************************
The following are the set of settings needed for specifying the module's basic functionality eSignature certification on a survey:

* Attestation settings; choose one of the following options:
    * Institution-Defined: Attestation language and labels defined by Institution and REDCap Admin
    * User-Defined: Define your own attestation language and labels within the module configurations
    * User-Defined & Controlled by MLM: Define your own certification/attestation and add your own labels to MLM.

* Instrument settings: Add and define settings for each survey that requires an eSignature Certification
    * Attestation Instrument - instrument name in which the survey eSignature attestation is needed
    * Attestation Language - select institution defined language or set your own
    * Logging of attestation - check enable and select a field that contains an identifier to add to logging instead of "survey respondent"   

**Note that**, the institution define attestation settings are defined by your REDCap Administrator. For example, the attestation text for MGB is the following:
> "I certify that all the information entered is correct. I understand that clicking 'Submit' will electronically sign the form and that signing this form electronically is the equivalent of signing a physical document."

Please note that the module's functionality is represented by the following matrix. The Institution-Defined settings
are available when the "Only English" or "Only Spanish" are selected; these are the languages set by your REDCap administrator.
The User-Defined setting is available to under the option by the same name, but also under MLM Controlled; that is, the user's
custom attestation language is added to the MLM workflow. Finally, the Attestation Language setting of MLM controlled provides
the Admins Defined attestations (i.e. "Only English" and "Only Spanish") plus the user-defined custom attestation language.


| Attestation Setting \ Attestation Language | Only <br> English | Only <br> Spanish | MLM <br> Controlled | User <br> Defined |
|--------------------------------------------|---------------|---------------|--------------|------------------------------------|
| Institution-Defined                        | X             | X             |  X           |                                    |
| User-Defined                               |               |               |              | X                                  |
| User-Defined & Controlled by MLM           | X             | X             |  X           |                                   |


### Advanced (Optional) Settings:
********************************************************************************

* <b>Server-Side PDF Processing:</b> optimize user-experience by enabling CRON for generating the PDF Archive file server-side. The resulting PDF file will be placed on the Project's "PDF Survey Archive" tab.
* <b>PDF Survey Archival</b> - check enable to generate and archive the survey's response of this instrument. The resulting PDF file will be placed on the Project's "PDF Survey Archive" tab.
* <b>PDF Survey Distribution</b> - check enable and select a field on an instrument to upload the PDF of the survey (can be used to send to participant via email notification)

### Note for REDCap Admin
********************************************************************************
Current supported language options are English and Spanish. In order to make these attestations compatible with MLM they have been given a language id that must match the corresponding language id in the project: "en" for English, and "sp" for Spanish.
Please note that the institution defined attestation settings (text) are not hardcoded and can be defined in the module's configuration window in REDCap's Controlled center.  
If Institutions require more than English and Spanish options, please see contact below. This is a work in progress.
If users require more than English and Spanish, the user can add and define one additional language at the project level.  

### License
********************************************************************************
See the [LICENSE](?prefix=self_service_ext_mod&page=LICENSE.md) file for details

#### Comments, questions, or concerns, please contact:
********************************************************************************
Ed Morales at EMORALES@BWH.HARVARD.EDU
