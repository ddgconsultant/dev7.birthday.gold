<?php
include($_SERVER['DOCUMENT_ROOT'] . '/core/site-controller.php');
include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/header2.inc');
?>
<!-- ===============================================-->
<!--   START REAL CONTENT:x-->
<!-- ===============================================-->




<!-- bootstrap-icons -->

<link rel="stylesheet" href="/public/js/plugins/min/css/bs5-bd-hr_main1.min.css">

<link href="/public/js/plugins/signature-pad/signature_pad.min.css" rel="stylesheet" media="screen">
<link href="/public/js/plugins/ladda/ladda-themeless.min.css" rel="stylesheet" media="screen">
<link rel="stylesheet" href="//cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">



</head>

<body>

    <h1 class="text-center">HR Employee Onboarding Forms</h1>

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-11 col-lg-10">
                <form id="bd-hr_main1" action="/hr/form_onboarding.php" method="POST" enctype="multipart/form-data" novalidate data-fv-no-icon="true" class="form-horizontal has-validator bs5-form">
                    <div>
                        <input name="bd-hr_main1-token" type="hidden" value="751102483660c703966a153.30393844"><input name="bd-hr_main1" type="hidden" value="true"><input name="g-recaptcha-response" type="hidden" value="">
                    </div>
                    <section>
                        <h1>Your Employment Forms</h1>
                        <p>Our HR system dynamically builds all the forms necessary for you to complete using this information. This allows us to perform your onboarding experience in a faster, more effiencent manner.


                            Espcially since you will not have to fill out multiple forms with the same information.</p>
                    </section>
                    <section>
                        <h2>Your Personal Contact Information</h2>
                        <div class="row mb-3"><label for="fullname" class="col-sm-4 col-form-label">Your Full Name</label>
                            <div class="col-sm-8"><input id="fullname" name="fullname" type="text" value="Richard Davis" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="username" class="col-sm-4 col-form-label">Your User Name</label>
                            <div class="col-sm-8"><input id="username" name="username" type="text" value="ddgconsultant" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="name_pref" class="col-sm-4 col-form-label">Your Preferred Name</label>
                            <div class="col-sm-8"><input id="name_pref" name="name_pref" type="text" value="Richard" class="form-control"></div>
                        </div>
                        <hr class="mt-2 mb-5">
                        <h5>Legal Details</h5>
                        <div class="row mb-3"><label for="name_first" class="col-sm-4 col-form-label">Legal First Name</label>
                            <div class="col-sm-8"><input id="name_first" name="name_first" type="text" value="Richard" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="name_middle" class="col-sm-4 col-form-label">Middle</label>
                            <div class="col-sm-8"><input id="name_middle" name="name_middle" type="text" value="M." class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="name_last" class="col-sm-4 col-form-label">Davis</label>
                            <div class="col-sm-8"><input id="name_last" name="name_last" type="text" value="" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="ssn" class="col-sm-4 col-form-label">SSN</label>
                            <div class="col-sm-8"><input id="ssn" name="ssn" type="password" value="" placeholder="###-##-####" class="form-control"></div>
                        </div>
                        <hr class="mt-2 mb-5">
                        <h5>Mailing Address</h5>
                        <div class="row mb-3"><label for="mail_address" class="col-sm-4 col-form-label">Address</label>
                            <div class="col-sm-8"><input id="mail_address" name="mail_address" type="text" value="" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="mail_city" class="col-sm-4 col-form-label">City</label>
                            <div class="col-sm-8"><input id="mail_city" name="mail_city" type="text" value="" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="mail_state" class="col-sm-4 col-form-label">State</label>
                            <div class="col-sm-8"><select id="mail_state" name="mail_state" class="form-select">
                                    <option value="alabama">Alabama</option>
                                    <option value="alaska">Alaska</option>
                                    <option value="arizona">Arizona</option>
                                    <option value="arkansas">Arkansas</option>
                                    <option value="california">California</option>
                                    <option value="colorado">Colorado</option>
                                    <option value="connecticut">Connecticut</option>
                                    <option value="delaware">Delaware</option>
                                    <option value="florida">Florida</option>
                                    <option value="georgia">Georgia</option>
                                    <option value="hawaii">Hawaii</option>
                                    <option value="idaho">Idaho</option>
                                    <option value="illinois">Illinois</option>
                                    <option value="indiana">Indiana</option>
                                    <option value="iowa">Iowa</option>
                                    <option value="kansas">Kansas</option>
                                    <option value="kentucky">Kentucky</option>
                                    <option value="louisiana">Louisiana</option>
                                    <option value="maine">Maine</option>
                                    <option value="maryland">Maryland</option>
                                    <option value="massachusetts">Massachusetts</option>
                                    <option value="michigan">Michigan</option>
                                    <option value="minnesota">Minnesota</option>
                                    <option value="mississippi">Mississippi</option>
                                    <option value="missouri">Missouri</option>
                                    <option value="montana">Montana</option>
                                    <option value="nebraska">Nebraska</option>
                                    <option value="nevada">Nevada</option>
                                    <option value="new_hampshire">New Hampshire</option>
                                    <option value="new_jersey">New Jersey</option>
                                    <option value="new_mexico">New Mexico</option>
                                    <option value="new_york">New York</option>
                                    <option value="north_carolina">North Carolina</option>
                                    <option value="north_dakota">North Dakota</option>
                                    <option value="ohio">Ohio</option>
                                    <option value="oklahoma">Oklahoma</option>
                                    <option value="oregon">Oregon</option>
                                    <option value="pennsylvania">Pennsylvania</option>
                                    <option value="rhode_island">Rhode Island</option>
                                    <option value="south_carolina">South Carolina</option>
                                    <option value="south_dakota">South Dakota</option>
                                    <option value="tennessee">Tennessee</option>
                                    <option value="texas">Texas</option>
                                    <option value="utah">Utah</option>
                                    <option value="vermont">Vermont</option>
                                    <option value="virginia">Virginia</option>
                                    <option value="washington">Washington</option>
                                    <option value="west_virginia">West Virginia</option>
                                    <option value="wisconsin">Wisconsin</option>
                                    <option value="wyoming">Wyoming</option>
                                </select></div>
                        </div>
                        <div class="row mb-3"><label for="mail_zip" class="col-sm-4 col-form-label">Zip</label>
                            <div class="col-sm-8"><input id="mail_zip" name="mail_zip" type="text" value="" class="form-control"></div>
                        </div>
                        <hr class="mt-2 mb-5">
                        <h5>Emergency Contact</h5>
                        <div class="row mb-3"><label for="e_contact1_name" class="col-sm-4 col-form-label">Contact Name</label>
                            <div class="col-sm-8"><input id="e_contact1_name" name="e_contact1_name" type="text" value="" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="e_contact1_phone" class="col-sm-4 col-form-label">Contact Phone</label>
                            <div class="col-sm-8"><input id="e_contact1_phone" name="e_contact1_phone" type="tel" value="" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="e_contact1_email" class="col-sm-4 col-form-label">Contact Email</label>
                            <div class="col-sm-8"><input id="e_contact1_email" name="e_contact1_email" type="email" value="" class="form-control"></div>
                        </div>
                        <div class="row mb-3"><label for="e_contact1_relationship" class="col-sm-4 col-form-label">Relationship</label>
                            <div class="col-sm-8"><select id="e_contact1_relationship" name="e_contact1_relationship" class="form-select">
                                    <option value="">Pick One</option>
                                    <option value="parent">Parent</option>
                                    <option value="spouse">Spouse</option>
                                    <option value="sibling">Sibling</option>
                                    <option value="child">Child</option>
                                    <option value="relative">Relative</option>
                                    <option value="friend">Friend</option>
                                    <option value="co_worker">Co-worker</option>
                                    <option value="legal_guardian">Legal Guardian</option>
                                    <option value="domestic_partner">Domestic Partner</option>
                                    <option value="other">Other</option>
                                </select></div>
                        </div>
                    </section>
                    <section>
                        <h1>Federal Employment Forms</h1>
                        <hr class="mt-2 mb-5">
                        <h2>W4 Tax Witholding Form</h2>
                        <div class="row mb-3"><label for="w4_filing_status" class="col-sm-4 col-form-label">Tax Filing Status</label>
                            <div class="col-sm-8"><select id="w4_filing_status" name="w4_filing_status" class="form-select">
                                    <option value="single_marriedseparately">Single or Maried filing separately</option>
                                    <option value="marriedfilingjoinly">Married filing joinly or Qualifing widow(er)</option>
                                    <option value="headofhouse">Head of household</option>
                                </select></div>
                        </div>
                        <div class="row mb-3"><label class="main-label main-label-inline col-sm-4 col-form-label">Only Check if...</label>
                            <div class="col-sm-8">
                                <div class="form-check form-check-inline"><input type="checkbox" id="w4_checkonly1_0" name="w4_checkonly1[]" value="1" data-toggle="false" class="form-check-input"><label for="w4_checkonly1_0" class="form-check-label">(1) hold more than one job at a time, or <br>(2) are married filing jointly and your spouse also works. The correct amount of withholding depends on income earned from all of these jobs.</label></div>
                            </div>
                        </div>
                        <div class="row mb-3"><label for="w4_completecheck" class="col-sm-4 col-form-label">Is your total income 200,000 or less 400,000 or less if married filing jointly):</label>
                            <div class="col-sm-8"><select id="w4_completecheck" name="w4_completecheck" class="form-select">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select></div>
                        </div>
                        <div class="hidden-wrapper off" data-parent="w4_completecheck" data-show-values="yes" data-inverse="">
                            <div class="row mb-3"><label for="w4_childdependents" class="col-sm-4 col-form-label">Number of Dependents under age 17</label>
                                <div class="col-sm-8"><input id="w4_childdependents" name="w4_childdependents" type="number" value="0" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="w4_childmultipler" class="col-sm-4 col-form-label">Multiply the number of qualifying children under age 17 by 2,000</label>
                                <div class="col-sm-8"><input id="w4_childmultipler" name="w4_childmultipler" type="text" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="w4_otherdependents" class="col-sm-4 col-form-label">Other Dependents</label>
                                <div class="col-sm-8"><input id="w4_otherdependents" name="w4_otherdependents" type="number" value="0" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="w4_othermultipler" class="col-sm-4 col-form-label">Multiply the number of other dependents by 500</label>
                                <div class="col-sm-8"><input id="w4_othermultipler" name="w4_othermultipler" type="text" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="w4_total" class="col-sm-4 col-form-label">Total</label>
                                <div class="col-sm-8">
                                    <div class="input-group has-addon-before">
                                        <div class="input-group-text"><span class="bi bi-currency-dollar phpfb-addon-before"></span></div><input id="w4_total" name="w4_total" type="text" value="" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3"><label for="w4_a" class="col-sm-4 col-form-label">(a) Other income not from jobs:</label>
                                <div class="col-sm-8">
                                    <div class="input-group has-addon-before">
                                        <div class="input-group-text"><span class="bi bi-currency-dollar phpfb-addon-before"></span></div><input id="w4_a" name="w4_a" type="text" value="" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3"><label for="w4_b" class="col-sm-4 col-form-label">(b) Deductions:</label>
                                <div class="col-sm-8">
                                    <div class="input-group has-addon-before">
                                        <div class="input-group-text"><span class="bi bi-currency-dollar phpfb-addon-before"></span></div><input id="w4_b" name="w4_b" type="text" value="" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3"><label for="w4_c" class="col-sm-4 col-form-label">(c) Extra withholding:</label>
                                <div class="col-sm-8">
                                    <div class="input-group has-addon-before">
                                        <div class="input-group-text"><span class="bi bi-currency-dollar phpfb-addon-before"></span></div><input id="w4_c" name="w4_c" type="text" value="" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="mt-2 mb-5">
                        <h2>I-9 Form - Employment Eligibility Verification</h2>
                        <div class="row mb-3"><label for="citizentype" class="col-sm-4 col-form-label">Select a Choice</label>
                            <div class="col-sm-8"><select id="citizentype" name="citizentype" class="form-select">
                                    <option value="option-1">A citizen of the United States</option>
                                    <option value="option-2">A noncitizen national of the United Stats (see Instructions)</option>
                                    <option value="option-3">A lawful permanent resident (Alien registration Number/USCIS Number)</option>
                                    <option value="option-4">An alien authorized to work</option>
                                </select></div>
                        </div>
                        <div class="hidden-wrapper off" data-parent="citizentype" data-show-values="option-2, option-3, option-4" data-inverse="">
                            <div class="row mb-3"><label for="input-20" class="col-sm-4 col-form-label">Alien Registration Number/USCIS Number</label>
                                <div class="col-sm-8"><input id="input-20" name="input-20" type="text" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="select-6" class="col-sm-4 col-form-label">Preparer and/or Translator Certification:</label>
                                <div class="col-sm-8"><select id="select-6" name="select-6" class="form-select">
                                        <option value="option-1">I did not use a preparer or translator</option>
                                        <option value="option-2">A preparer(s) and/or translator(s) assisted the employee in completing Section 1.</option>
                                    </select></div>
                            </div>
                        </div>
                        <div class="row mb-3"><label class="main-label main-label-inline col-sm-4 col-form-label">I have a valid, unexpired US Passport</label>
                            <div class="col-sm-8">
                                <div class="form-check form-check-inline"><input type="radio" id="i9_uspassport_0" name="i9_uspassport" value="yes" data-toggle="false" class="form-check-input"><label for="i9_uspassport_0" class="form-check-label">Yes</label></div>
                                <div class="form-check form-check-inline"><input type="radio" id="i9_uspassport_1" name="i9_uspassport" value="no" data-toggle="false" class="form-check-input"><label for="i9_uspassport_1" class="form-check-label">No</label></div>
                            </div>
                        </div>
                        <div class="hidden-wrapper off" data-parent="i9_uspassport" data-show-values="yes" data-inverse="">
                            <h5>Upload List A Document - Passport</h5>
                            <div class="row mb-3"><label for="uploader-i9_passport_full" class="col-sm-4 col-form-label fileinput-label">Image of US Passport</label>
                                <div class="col-sm-8"><input type="file" name="uploader-i9_passport_full" id="uploader-i9_passport_full" class="form-control" data-fileuploader-listInput="i9_passport_full" data-fileuploader-files='[""]'></div>
                            </div>
                        </div>
                        <div class="hidden-wrapper off" data-parent="i9_uspassport" data-show-values="no" data-inverse="">
                            <hr>
                            <h5>Upload List B Document</h5>
                            <div class="row mb-3"><label for="i9_listb_documenttype" class="col-sm-4 col-form-label">List B Document Type</label>
                                <div class="col-sm-8"><select id="i9_listb_documenttype" name="i9_listb_documenttype" class="form-select">
                                        <option value="us_driver_license_or_id">Driver's license or ID card issued by a State or outlying possession of the U.S. with photo or personal info</option>
                                        <option value="government_issued_id">ID card issued by federal, state or local government with photo or personal info</option>
                                        <option value="school_id_with_photo">School ID card with a photograph</option>
                                        <option value="voter_registration_card">Voter's registration card</option>
                                        <option value="us_military_card_or_draft_record">U.S. Military card or draft record</option>
                                        <option value="military_dependent_id_card">Military dependent's ID card</option>
                                        <option value="us_coast_guard_merchant_mariner_card">U.S. Coast Guard Merchant Mariner Card</option>
                                        <option value="native_american_tribal_document">Native American tribal document</option>
                                        <option value="canadian_driver_license">Driver's license issued by a Canadian government authority</option>
                                        <option value="school_record_or_report_card">School record or report card (for persons under age 18)</option>
                                        <option value="clinic_doctor_hospital_record">Clinic, doctor, or hospital record (for persons under age 18)</option>
                                        <option value="daycare_nursery_school_record">Day-care or nursery school record (for persons under age 18)</option>
                                        <option value="receipt_for_replacement_list_b">Receipt for a replacement of a lost, stolen, or damaged List B document (temporary)</option>
                                    </select></div>
                            </div>
                            <div class="row mb-3"><label for="i9_listb_authority" class="col-sm-4 col-form-label">List B Document Issuing Authority</label>
                                <div class="col-sm-8"><input id="i9_listb_authority" name="i9_listb_authority" type="text" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="i9_listb_exp" class="col-sm-4 col-form-label">List B Document Expiration Date</label>
                                <div class="col-sm-8"><input id="i9_listb_exp" name="i9_listb_exp" type="date" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="uploader-i9_listb_upload" class="col-sm-4 col-form-label fileinput-label">Upload Front and Back Images</label>
                                <div class="col-sm-8"><input type="file" name="uploader-i9_listb_upload" id="uploader-i9_listb_upload" class="form-control" data-fileuploader-listInput="i9_listb_upload" data-fileuploader-files='[""]'></div>
                            </div>
                            <hr>
                            <h5>Upload List C Document</h5>
                            <div class="row mb-3"><label for="i9_listc_documenttype" class="col-sm-4 col-form-label">Select 8</label>
                                <div class="col-sm-8"><select id="i9_listc_documenttype" name="i9_listc_documenttype" class="form-select">
                                        <option value="us_social_security_card">U.S. Social Security Card</option>
                                        <option value="certification_of_birth_abroad">Certification of report of birth issued by the Department of State (Forms DS-1350, FS-545, FS-240)</option>
                                        <option value="us_birth_certificate">Original or certified copy of birth certificate issued by a State, county, municipal authority, or territory of the U.S. with official seal</option>
                                        <option value="native_american_tribal_document_c">Native American tribal document</option>
                                        <option value="us_citizen_id_card">U.S. Citizen ID Card (Form I-197)</option>
                                        <option value="resident_citizen_id_card">Identification Card for Use of Resident Citizen in the United States (Form I-179)</option>
                                        <option value="dhs_employment_authorization">Employment authorization document issued by the Department of Homeland Security (DHS)</option>
                                        <option value="receipt_for_replacement_list_c">Receipt for a replacement of a lost, stolen, or damaged List C document (temporary)</option>
                                    </select></div>
                            </div>
                            <div class="row mb-3"><label for="i9_listc_authority" class="col-sm-4 col-form-label">List C Document Issuing Authority</label>
                                <div class="col-sm-8"><input id="i9_listc_authority" name="i9_listc_authority" type="text" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="i9_listc_exp" class="col-sm-4 col-form-label">List C Document Expiration Date</label>
                                <div class="col-sm-8"><input id="i9_listc_exp" name="i9_listc_exp" type="date" value="" class="form-control"></div>
                            </div>
                            <div class="row mb-3"><label for="uploader-i9_listc_upload" class="col-sm-4 col-form-label fileinput-label">Upload Front and Back Images</label>
                                <div class="col-sm-8"><input type="file" name="uploader-i9_listc_upload" id="uploader-i9_listc_upload" class="form-control" data-fileuploader-listInput="i9_listc_upload" data-fileuploader-files='[""]'></div>
                            </div>
                        </div>
                    </section>
                    <section>
                        <h1>Payroll Forms</h1>
                        <p>At this time, Birthday.Gold does not withold your federal and state income taxes. It is important that you plan to pay your tax liability on your own.

                            This will change in the near future and you will be notified in writing when we implement tax withholdings.</p>
                        <div class="row mb-3"><label for="payroll_pay_vendor" class="col-sm-4 col-form-label">Get paid via</label>
                            <div class="col-sm-8"><select id="payroll_pay_vendor" name="payroll_pay_vendor" class="form-select">
                                    <option value="paypal">Paypal</option>
                                    <option value="venmo">Vemno</option>
                                    <option value="cashapp">Cashapp</option>
                                </select></div>
                        </div>
                        <div class="row mb-3"><label for="payroll_account_username" class="col-sm-4 col-form-label">Account Username</label>
                            <div class="col-sm-8"><input id="payroll_account_username" name="payroll_account_username" type="text" value="" class="form-control"></div>
                        </div>
                    </section>
                    <section>
                        <h1>Signature</h1>
                        <div class="row mb-3"><label class="main-label main-label-inline col-sm-4 col-form-label">Acknowledgements</label>
                            <div class="col-sm-8">
                                <div class="form-check form-check-inline"><input type="checkbox" id="acknowledgements_0" name="acknowledgements[]" value="value-1" data-toggle="false" class="form-check-input"><label for="acknowledgements_0" class="form-check-label">I understand</label></div>
                                <div class="form-check form-check-inline"><input type="checkbox" id="acknowledgements_1" name="acknowledgements[]" value="value-2" data-toggle="false" class="form-check-input"><label for="acknowledgements_1" class="form-check-label">NDA</label></div>
                                <div class="form-check form-check-inline"><input type="checkbox" id="acknowledgements_2" name="acknowledgements[]" value="value-3" data-toggle="false" class="form-check-input"><label for="acknowledgements_2" class="form-check-label">TimeKeeping Policy</label></div>
                                <div class="form-check form-check-inline"><input type="checkbox" id="acknowledgements_3" name="acknowledgements[]" value="value-4" data-toggle="false" class="form-check-input"><label for="acknowledgements_3" class="form-check-label">At-Will Employment</label></div>
                                <div class="form-check form-check-inline"><input type="checkbox" id="acknowledgements_4" name="acknowledgements[]" value="value-5" data-toggle="false" class="form-check-input"><label for="acknowledgements_4" class="form-check-label">Future Benefits</label></div>
                            </div>
                        </div>
                    </section>
                    <div class="row mb-3"><label for="user-signature" class="col-sm-4 col-form-label">Sign to confirm your agreement</label>
                        <div class="col-sm-8"><input id="user-signature" name="user-signature" type="hidden" value="" data-signature-pad="true" data-background-color="#F7F7F7" data-pen-color="#333" data-width="100%" data-clear-button="true" data-clear-button-class="btn btn-warning" data-clear-button-text="clear"><canvas id="user-signature-canvas" class="signature-pad-canvas"></canvas></div>
                    </div>
                    <div class="row phpfb-centered">
                        <div class="col-sm-offset-4 col-sm-8"><button type="submit" name="submit-btn" value="1" class="btn btn-primary" data-ladda-button="true" data-style="zoom-in">Agree <i class="bi bi-check-lg ms-2" aria-hidden="true"></i></button></div>
                    </div>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>















    <!-- /Footer -->































    <!-- Bootstrap 5 JavaScript -->
    <script src="/public/js/plugins/signature-pad/signature_pad.min.js"></script>
    <script src="/public/js/plugins/min/js/bs5-bd-hr_main1.min.js"></script>

    <script src="/public/js/plugins/ladda/spin.min.js"></script>
    <script src="/public/js/plugins/ladda/ladda.min.js"></script>
    <script src="/public/js/plugins/formvalidation/js/FormValidation.full.min.js"></script>
    <script src="/public/js/plugins/formvalidation/js/locales/en_US.min.js"></script>
    <script src="/public/js/plugins/formvalidation/js/plugins/AutoFocus.min.js"></script>
    <script src="/public/js/plugins/formvalidation/js/plugins/Transformer.min.js"></script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ka7Sk0Gln4gmtz2MlQnikT1wXgYsOg+OMhuP+IlRH9sENBO0LRn5q+8nbTov4+1p" crossorigin="anonymous">
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- 
    <script src="/public/js/plugins/min/js/bs5-bd-hr_main1.min.js" defer></script>
    <script src="https://www.google.com/recaptcha/api.js?render=6LfKkfQnAAAAADivF6ErR38G1xqCHmfOWKwZykUJ"></script>
-->



    <script>
        if (typeof forms === "undefined") {
            var forms = [];
        }
        forms["bd-hr_main1"] = {};
        document.addEventListener('DOMContentLoaded', function(event) {
            if (top != self && typeof location.ancestorOrigins != "undefined") {
                if (location.ancestorOrigins[0] !== "https://preview.codecanyon.net" && !document.getElementById("drag-and-drop-preview") && document.getElementById("bd-hr_main1")) {
                    document.getElementById("bd-hr_main1").addEventListener("submit", function(e) {
                        e.preventDefault();
                        console.log("not allowed");
                        return false;
                    });
                }
            }

            if (typeof(phpfbDependentFields) == "undefined") {
                window.phpfbDependentFields = [];
            }
            phpfbDependentFields["#bd-hr_main1 .hidden-wrapper"] = new DependentFields("#bd-hr_main1 .hidden-wrapper");
            document.body.classList.add('dependent-fields-loaded');
            var inputName0 = $("#bd-hr_main1 #uploader-i9_passport_full").attr('name'),
                $form = $('input[name="' + inputName0 + '"]').closest('form'),
                $submit = $form.find('button[type="submit"]'),
                form = forms['bd-hr_main1'],
                originalDisabledState = $submit.prop('disabled'),
                debug0 = true;

            if (typeof(validateUpload0) === 'undefined') {
                var validateUpload0 = function() {
                    if (typeof(form.fv) == 'object') {
                        if (inputName0 in form.fv.elements) {
                            form.fv.validateField(inputName0);
                        } else if (inputName0 + '[]' in form.fv.elements) {
                            form.fv.validateField(inputName0 + '[]');
                        }
                    }
                }
            }

            $("#bd-hr_main1 #uploader-i9_passport_full").fileuploader({
                enableApi: true,
                limit: 1,
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                fileMaxSize: 5,
                beforeSelect: function(files, listEl, parentEl, newInputEl, inputEl) {
                    var iName0 = inputName0; // if uploader limit = 1
                    if ($('input[name="' + inputName0 + '[]"]')[0]) { // if several files allowed
                        iName0 += '[]';
                    }
                    if ($('input[name="' + iName0 + '"]').attr('required') && typeof(form.fv) == 'object' && iName0 in form.fv.elements) {
                        // replace the file input value by the hidden input value for the jQuery validation plugin
                        var hiddenInputName = iName0.replace(/^uploader-/, '').replace(/\[\]$/, '');
                        var o = new Object();
                        o[iName0] = {
                            notEmpty: function(field, element, validator) {
                                let value = $('input[name="' + hiddenInputName + '"]').val().replace(/\[\]$/, '');
                                return value;
                            }
                        };
                        form.fv.registerPlugin(
                            'transformer', new FormValidation.plugins.Transformer(o)
                        );
                    }

                    return true;
                },
                upload: {
                    url: '/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php',
                    data: {
                        input_name: inputName0,
                        hash: 'f10f3079af19dcfc3456a3d4544ec06edab68e84',
                        form_id: 'bd-hr_main1'
                    },
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    start: true,
                    synchron: true,
                    onSuccess: function(data, item, listEl, parentEl, newInputEl, inputEl, textStatus, jqXHR) {
                        $submit.prop('disabled', originalDisabledState);

                        try {
                            data = JSON.parse(data);
                            item.name = data.files[0].name;
                            item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                        } catch (e) {
                            if (debug0 === true) {
                                console.log(data);
                                if (data.warnings.length > 0) {
                                    item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-warning has-icon mt-2 mb-0'>" + data.warnings[0] + "</p>");
                                }
                                item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-danger has-icon mt-2 mb-0'><h5>Something went wrong with the uploader.</h5><p>You may have to create the upload folder and/or the thumbnails folders manually, or your upload folder is not writable.</p><p>If you generate thumbnails your upload folder must follow this structure:</p><pre><code>[your-upload-folder] \n    => thumbs \n        => lg \n        => md \n        => sm</code></pre><p class='mb-0'><strong>Open your browser console for more information.</strong></p></div>");
                            }
                        }

                        // make HTML changes
                        item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                        validateUpload0();

                        setTimeout(function() {
                            item.html.find('.progress-bar2').fadeOut(400);
                        }, 400);
                    },
                    onError: function(item, listEl, parentEl, newInputEl, inputEl, jqXHR, textStatus, errorThrown) {
                        $submit.prop('disabled', originalDisabledState);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.find('span').html(0 + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                            item.html.find('.progress-bar2').fadeOut(400);
                        }

                        item.upload.status != 'cancelled' && item.html.find('.fileuploader-action-retry').length == 0 ? item.html.find('.column-actions').prepend(
                            '<a class="fileuploader-action fileuploader-action-retry" title="Retry"><i></i></a>'
                        ) : null;
                    },
                    onProgress: function(data, item) {
                        $submit.prop('disabled', true);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.show();
                            progressBar.find('span').html(data.percentage + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                        }
                    },
                    onComplete: null
                },
                onRemove: function(item) {
                    $submit.prop('disabled', originalDisabledState);
                    // send POST request
                    $.post('/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_remove_file.php', {
                        input_name: inputName0,
                        hash: 'f10f3079af19dcfc3456a3d4544ec06edab68e84',
                        form_id: 'bd-hr_main1',
                        filename: item.name,
                        upload_dir: '/hr/file-uploads/'
                    }, function() {
                        validateUpload0();
                    });
                },
                onEmpty: function(listEl, parentEl, newInputEl, inputEl) {
                    validateUpload0();
                },
                // captions
                captions: {
                    button: function(options) {
                        return 'Browse ' + (options.limit == 1 ? 'file' : 'files');
                    },
                    feedback: function(options) {
                        return 'Choose ' + (options.limit == 1 ? 'file' : 'files') + ' to upload';
                    },
                    feedback2: function(options) {
                        return options.length + ' ' + (options.length > 1 ? ' files were' : ' file was') + ' chosen';
                    },
                    confirm: 'Confirm',
                    cancel: 'Cancel',
                    name: 'Name',
                    type: 'Type',
                    size: 'Size',
                    dimensions: 'Dimensions',
                    duration: 'Duration',
                    crop: 'Crop',
                    rotate: 'Rotate',
                    sort: 'Sort',
                    download: 'Download',
                    remove: 'Remove',
                    drop: 'Drop the files here to Upload',
                    paste: '<div class="fileuploader-pending-loader"></div> Pasting a file, click here to cancel.',
                    removeConfirmation: 'Are you sure you want to remove this file?',
                    errors: {
                        filesLimit: 'Only ${limit} files are allowed to be uploaded.',
                        filesType: 'Only ${extensions} files are allowed to be uploaded.',
                        fileSize: '${name} is too large! Please choose a file up to ${fileMaxSize}MB.',
                        filesSizeAll: 'Files that you chose are too large! Please upload files up to ${maxSize} MB.',
                        fileName: 'File with the name ${name} is already selected.',
                        folderUpload: 'You are not allowed to upload folders.'
                    }
                }
            });
            var inputName1 = $("#bd-hr_main1 #uploader-i9_listb_upload").attr('name'),
                $form = $('input[name="' + inputName1 + '"]').closest('form'),
                $submit = $form.find('button[type="submit"]'),
                form = forms['bd-hr_main1'],
                originalDisabledState = $submit.prop('disabled'),
                debug1 = true;

            if (typeof(validateUpload1) === 'undefined') {
                var validateUpload1 = function() {
                    if (typeof(form.fv) == 'object') {
                        if (inputName1 in form.fv.elements) {
                            form.fv.validateField(inputName1);
                        } else if (inputName1 + '[]' in form.fv.elements) {
                            form.fv.validateField(inputName1 + '[]');
                        }
                    }
                }
            }

            $("#bd-hr_main1 #uploader-i9_listb_upload").fileuploader({
                enableApi: true,
                limit: 2,
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                fileMaxSize: 10,
                beforeSelect: function(files, listEl, parentEl, newInputEl, inputEl) {
                    var iName1 = inputName1; // if uploader limit = 1
                    if ($('input[name="' + inputName1 + '[]"]')[0]) { // if several files allowed
                        iName1 += '[]';
                    }
                    if ($('input[name="' + iName1 + '"]').attr('required') && typeof(form.fv) == 'object' && iName1 in form.fv.elements) {
                        // replace the file input value by the hidden input value for the jQuery validation plugin
                        var hiddenInputName = iName1.replace(/^uploader-/, '').replace(/\[\]$/, '');
                        var o = new Object();
                        o[iName1] = {
                            notEmpty: function(field, element, validator) {
                                let value = $('input[name="' + hiddenInputName + '"]').val().replace(/\[\]$/, '');
                                return value;
                            }
                        };
                        form.fv.registerPlugin(
                            'transformer', new FormValidation.plugins.Transformer(o)
                        );
                    }

                    return true;
                },
                upload: {
                    url: '/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php',
                    data: {
                        input_name: inputName1,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1'
                    },
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    start: true,
                    synchron: true,
                    onSuccess: function(data, item, listEl, parentEl, newInputEl, inputEl, textStatus, jqXHR) {
                        $submit.prop('disabled', originalDisabledState);

                        try {
                            data = JSON.parse(data);
                            item.name = data.files[0].name;
                            item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                        } catch (e) {
                            if (debug1 === true) {
                                console.log(data);
                                if (data.warnings.length > 0) {
                                    item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-warning has-icon mt-2 mb-0'>" + data.warnings[0] + "</p>");
                                }
                                item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-danger has-icon mt-2 mb-0'><h5>Something went wrong with the uploader.</h5><p>You may have to create the upload folder and/or the thumbnails folders manually, or your upload folder is not writable.</p><p>If you generate thumbnails your upload folder must follow this structure:</p><pre><code>[your-upload-folder] \n    => thumbs \n        => lg \n        => md \n        => sm</code></pre><p class='mb-0'><strong>Open your browser console for more information.</strong></p></div>");
                            }
                        }

                        // make HTML changes
                        item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                        validateUpload1();

                        setTimeout(function() {
                            item.html.find('.progress-bar2').fadeOut(400);
                        }, 400);
                    },
                    onError: function(item, listEl, parentEl, newInputEl, inputEl, jqXHR, textStatus, errorThrown) {
                        $submit.prop('disabled', originalDisabledState);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.find('span').html(0 + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                            item.html.find('.progress-bar2').fadeOut(400);
                        }

                        item.upload.status != 'cancelled' && item.html.find('.fileuploader-action-retry').length == 0 ? item.html.find('.column-actions').prepend(
                            '<a class="fileuploader-action fileuploader-action-retry" title="Retry"><i></i></a>'
                        ) : null;
                    },
                    onProgress: function(data, item) {
                        $submit.prop('disabled', true);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.show();
                            progressBar.find('span').html(data.percentage + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                        }
                    },
                    onComplete: null
                },
                onRemove: function(item) {
                    $submit.prop('disabled', originalDisabledState);
                    // send POST request
                    $.post('/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_remove_file.php', {
                        input_name: inputName1,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1',
                        filename: item.name,
                        upload_dir: '/hr/file-uploads/'
                    }, function() {
                        validateUpload1();
                    });
                },
                onEmpty: function(listEl, parentEl, newInputEl, inputEl) {
                    validateUpload1();
                },
                // captions
                captions: {
                    button: function(options) {
                        return 'Browse ' + (options.limit == 1 ? 'file' : 'files');
                    },
                    feedback: function(options) {
                        return 'Choose ' + (options.limit == 1 ? 'file' : 'files') + ' to upload';
                    },
                    feedback2: function(options) {
                        return options.length + ' ' + (options.length > 1 ? ' files were' : ' file was') + ' chosen';
                    },
                    confirm: 'Confirm',
                    cancel: 'Cancel',
                    name: 'Name',
                    type: 'Type',
                    size: 'Size',
                    dimensions: 'Dimensions',
                    duration: 'Duration',
                    crop: 'Crop',
                    rotate: 'Rotate',
                    sort: 'Sort',
                    download: 'Download',
                    remove: 'Remove',
                    drop: 'Drop the files here to Upload',
                    paste: '<div class="fileuploader-pending-loader"></div> Pasting a file, click here to cancel.',
                    removeConfirmation: 'Are you sure you want to remove this file?',
                    errors: {
                        filesLimit: 'Only ${limit} files are allowed to be uploaded.',
                        filesType: 'Only ${extensions} files are allowed to be uploaded.',
                        fileSize: '${name} is too large! Please choose a file up to ${fileMaxSize}MB.',
                        filesSizeAll: 'Files that you chose are too large! Please upload files up to ${maxSize} MB.',
                        fileName: 'File with the name ${name} is already selected.',
                        folderUpload: 'You are not allowed to upload folders.'
                    }
                }
            });
            var inputName2 = $("#bd-hr_main1 #uploader-i9_listc_upload").attr('name'),
                $form = $('input[name="' + inputName2 + '"]').closest('form'),
                $submit = $form.find('button[type="submit"]'),
                form = forms['bd-hr_main1'],
                originalDisabledState = $submit.prop('disabled'),
                debug2 = true;

            if (typeof(validateUpload2) === 'undefined') {
                var validateUpload2 = function() {
                    if (typeof(form.fv) == 'object') {
                        if (inputName2 in form.fv.elements) {
                            form.fv.validateField(inputName2);
                        } else if (inputName2 + '[]' in form.fv.elements) {
                            form.fv.validateField(inputName2 + '[]');
                        }
                    }
                }
            }

            $("#bd-hr_main1 #uploader-i9_listc_upload").fileuploader({
                enableApi: true,
                limit: 2,
                extensions: ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
                fileMaxSize: 10,
                beforeSelect: function(files, listEl, parentEl, newInputEl, inputEl) {
                    var iName2 = inputName2; // if uploader limit = 1
                    if ($('input[name="' + inputName2 + '[]"]')[0]) { // if several files allowed
                        iName2 += '[]';
                    }
                    if ($('input[name="' + iName2 + '"]').attr('required') && typeof(form.fv) == 'object' && iName2 in form.fv.elements) {
                        // replace the file input value by the hidden input value for the jQuery validation plugin
                        var hiddenInputName = iName2.replace(/^uploader-/, '').replace(/\[\]$/, '');
                        var o = new Object();
                        o[iName2] = {
                            notEmpty: function(field, element, validator) {
                                let value = $('input[name="' + hiddenInputName + '"]').val().replace(/\[\]$/, '');
                                return value;
                            }
                        };
                        form.fv.registerPlugin(
                            'transformer', new FormValidation.plugins.Transformer(o)
                        );
                    }

                    return true;
                },
                upload: {
                    url: '/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_upload_file.php',
                    data: {
                        input_name: inputName2,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1'
                    },
                    type: 'POST',
                    enctype: 'multipart/form-data',
                    start: true,
                    synchron: true,
                    onSuccess: function(data, item, listEl, parentEl, newInputEl, inputEl, textStatus, jqXHR) {
                        $submit.prop('disabled', originalDisabledState);

                        try {
                            data = JSON.parse(data);
                            item.name = data.files[0].name;
                            item.html.find('.column-title > div:first-child').text(data.files[0].name).attr('title', data.files[0].name);
                        } catch (e) {
                            if (debug2 === true) {
                                console.log(data);
                                if (data.warnings.length > 0) {
                                    item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-warning has-icon mt-2 mb-0'>" + data.warnings[0] + "</p>");
                                }
                                item.html.append("<p class='mt-2'>&nbsp;</p><div class='alert alert-danger has-icon mt-2 mb-0'><h5>Something went wrong with the uploader.</h5><p>You may have to create the upload folder and/or the thumbnails folders manually, or your upload folder is not writable.</p><p>If you generate thumbnails your upload folder must follow this structure:</p><pre><code>[your-upload-folder] \n    => thumbs \n        => lg \n        => md \n        => sm</code></pre><p class='mb-0'><strong>Open your browser console for more information.</strong></p></div>");
                            }
                        }

                        // make HTML changes
                        item.html.find('.fileuploader-action-remove').addClass('fileuploader-action-success');

                        validateUpload2();

                        setTimeout(function() {
                            item.html.find('.progress-bar2').fadeOut(400);
                        }, 400);
                    },
                    onError: function(item, listEl, parentEl, newInputEl, inputEl, jqXHR, textStatus, errorThrown) {
                        $submit.prop('disabled', originalDisabledState);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.find('span').html(0 + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(0 + "%");
                            item.html.find('.progress-bar2').fadeOut(400);
                        }

                        item.upload.status != 'cancelled' && item.html.find('.fileuploader-action-retry').length == 0 ? item.html.find('.column-actions').prepend(
                            '<a class="fileuploader-action fileuploader-action-retry" title="Retry"><i></i></a>'
                        ) : null;
                    },
                    onProgress: function(data, item) {
                        $submit.prop('disabled', true);
                        var progressBar = item.html.find('.progress-bar2');

                        if (progressBar.length > 0) {
                            progressBar.show();
                            progressBar.find('span').html(data.percentage + "%");
                            progressBar.find('.fileuploader-progressbar .bar').width(data.percentage + "%");
                        }
                    },
                    onComplete: null
                },
                onRemove: function(item) {
                    $submit.prop('disabled', originalDisabledState);
                    // send POST request
                    $.post('/core/applications/phpformbuilder/plugins/fileuploader/default/php/ajax_remove_file.php', {
                        input_name: inputName2,
                        hash: 'd0512044ad57e63e8b0c858b5db317bcf20d6138',
                        form_id: 'bd-hr_main1',
                        filename: item.name,
                        upload_dir: '/hr/file-uploads/'
                    }, function() {
                        validateUpload2();
                    });
                },
                onEmpty: function(listEl, parentEl, newInputEl, inputEl) {
                    validateUpload2();
                },
                // captions
                captions: {
                    button: function(options) {
                        return 'Browse ' + (options.limit == 1 ? 'file' : 'files');
                    },
                    feedback: function(options) {
                        return 'Choose ' + (options.limit == 1 ? 'file' : 'files') + ' to upload';
                    },
                    feedback2: function(options) {
                        return options.length + ' ' + (options.length > 1 ? ' files were' : ' file was') + ' chosen';
                    },
                    confirm: 'Confirm',
                    cancel: 'Cancel',
                    name: 'Name',
                    type: 'Type',
                    size: 'Size',
                    dimensions: 'Dimensions',
                    duration: 'Duration',
                    crop: 'Crop',
                    rotate: 'Rotate',
                    sort: 'Sort',
                    download: 'Download',
                    remove: 'Remove',
                    drop: 'Drop the files here to Upload',
                    paste: '<div class="fileuploader-pending-loader"></div> Pasting a file, click here to cancel.',
                    removeConfirmation: 'Are you sure you want to remove this file?',
                    errors: {
                        filesLimit: 'Only ${limit} files are allowed to be uploaded.',
                        filesType: 'Only ${extensions} files are allowed to be uploaded.',
                        fileSize: '${name} is too large! Please choose a file up to ${fileMaxSize}MB.',
                        filesSizeAll: 'Files that you chose are too large! Please upload files up to ${maxSize} MB.',
                        fileName: 'File with the name ${name} is already selected.',
                        folderUpload: 'You are not allowed to upload folders.'
                    }
                }
            });
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = '/core/applications/phpformbuilder/plugins/formvalidation/js/plugins/Bootstrap5.js';

            document.head.appendChild(script);

            script.onload = function() {
                const intPhoneNumber = function() {
                    return {
                        validate: function(input) {
                            let formId = document.querySelector("#bd-hr_main1").getAttribute('id'),
                                itiInstance = iti['#' + formId + ' ' + '#' + input.element.id];
                            if (input.element.required === false && input.element.value === '') {
                                return {
                                    valid: true
                                };
                            }
                            if (itiInstance.isValidNumber()) {
                                return {
                                    valid: true
                                };
                            }
                            var countryData = itiInstance.getSelectedCountryData();
                            return {
                                valid: false,
                                message: form.fv.localization.phone.country.replace("%s", countryData.name)
                            };
                        },
                    };
                };

                const tinymceNotEmpty = function() {
                    return {
                        validate: function(input) {
                            // Get the plain text without HTML
                            const text = tinyMCE.activeEditor.getContent({
                                format: 'text',
                            });

                            if (text.length > 0) {
                                return {
                                    valid: true
                                };
                            }
                            return {
                                valid: false
                            };
                        }
                    };
                };

                var formId = document.querySelector("#bd-hr_main1").getAttribute('id'),
                    dataAttr = document.querySelector("#bd-hr_main1").dataset,
                    form = forms[formId],
                    frameworkPlugin,
                    isDefaultMessageContainer = true,
                    messagePlugin;

                if (document.querySelector('#bd-hr_main1 button[name="submit"]')) {
                    alert('The Formvalidation plugin does not allow to name the submit button "submit". You have to rename it or the form will not work.');
                }

                if (document.querySelector('#bd-hr_main1 input[type="hidden"][required]')) {
                    alert('The Formvalidation plugin does not allow the "required" attribute on an hidden input. Remove the "required" attribute or the form will not work.');
                }

                let isBulmaHorizontal = document.querySelector("#bd-hr_main1").classList.contains('bulma-form') && document.querySelector("#bd-hr_main1").classList.contains('bulma-form-horizontal'),
                    isBs5 = document.querySelector("#bd-hr_main1").classList.contains('bs5-form'),
                    isBs5Horizontal = document.querySelector("#bd-hr_main1").classList.contains('bs5-form') && document.querySelector("#bd-hr_main1").classList.contains('form-horizontal'),
                    isBs4 = document.querySelector("#bd-hr_main1").classList.contains('bs4-form'),
                    isBs4Horizontal = document.querySelector("#bd-hr_main1").classList.contains('bs4-form') && document.querySelector("#bd-hr_main1").classList.contains('form-horizontal'),
                    isFoundationHorizontal = document.querySelector("#bd-hr_main1").classList.contains('foundation-form') && document.querySelector("#bd-hr_main1").classList.contains('form-horizontal'),
                    isMaterial = document.querySelector("#bd-hr_main1").classList.contains('material-form'),
                    isTailwind = document.querySelector("#bd-hr_main1").classList.contains('tailwind-form'),
                    isUikit = document.querySelector("#bd-hr_main1").classList.contains('uikit-form'),
                    isUikitVertical = document.querySelector("#bd-hr_main1").classList.contains('uk-form-stacked');

                if (isBulmaHorizontal) {
                    isDefaultMessageContainer = false;
                    messagePlugin = new FormValidation.plugins.Message({
                        clazz: 'help is-danger',
                        container: function(field, ele) {
                            return ele.closest('.column');
                        }
                    });
                } else {
                    messagePlugin = new FormValidation.plugins.Message();
                }

                if (isBs5 || isBs4) {
                    if (isBs5Horizontal || isBs4Horizontal) {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="col-"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    } else if (isBs5) {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="bs5-form-stacked-element"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    } else {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer
                        });
                    }
                } else if (isBulmaHorizontal) {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer,
                        rowSelector: function(field, ele) {
                            // get the 1st class of closest parent div
                            var classList = ele.closest('div[class*="column"]').getAttribute('class').split(' ').filter(Boolean);
                            return '.' + classList[0];
                        }
                    });
                } else if (isMaterial) {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer,
                        rowSelector: function(field, ele) {
                            if (ele.classList.contains('fv-group')) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                            return '.input-field';
                        }
                    });
                } else if (isTailwind) {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer,
                        rowSelector: function(field, ele) {
                            // get the 1st class of closest parent div
                            var classList = ele.closest('div[class*="grid-"]').getAttribute('class').split(' ').filter(Boolean);
                            return '.' + classList[0];
                        }
                    });
                } else if (isUikit) {
                    if (isUikitVertical) {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="uk-form-stacked-element"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    } else {
                        frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                            defaultMessageContainer: isDefaultMessageContainer,
                            rowSelector: function(field, ele) {
                                // get the 1st class of closest parent div
                                var classList = ele.closest('div[class*="uk-width-"]').getAttribute('class').split(' ').filter(Boolean);
                                return '.' + classList[0];
                            }
                        });
                    }
                } else {
                    frameworkPlugin = new FormValidation.plugins.Bootstrap5({
                        defaultMessageContainer: isDefaultMessageContainer
                    });
                }
                form.fv = FormValidation.formValidation(
                        document.querySelector("#bd-hr_main1"), {
                            locale: 'en_US',
                            localization: FormValidation.locales.en_US,
                            plugins: {
                                bootstrap: frameworkPlugin,
                                declarative: new FormValidation.plugins.Declarative({
                                    html5Input: true
                                }),
                                aria: new FormValidation.plugins.Aria(),
                                autoFocus: new FormValidation.plugins.AutoFocus(),
                                excluded: new FormValidation.plugins.Excluded({
                                    excluded: function(field, element, elements) {
                                        // return true to exclude the field
                                        var parentHiddenWrapper = element.closest(['.hidden-wrapper:not(.on) *']);

                                        if (parentHiddenWrapper !== null) {
                                            if (dataAttr.fvDebug !== undefined) {
                                                console.log('%c' + field + ': validation skipped', 'color: #666');
                                            }
                                            return true;
                                        }
                                        return false;
                                    }
                                }),
                                icon: new FormValidation.plugins.Icon(),
                                message: messagePlugin,
                                sequence: new FormValidation.plugins.Sequence({
                                    enabled: true,
                                }),
                                submitButton: new FormValidation.plugins.SubmitButton(),
                                trigger: new FormValidation.plugins.Trigger()
                            }
                        }
                    )
                    .registerValidator('intphonenumber', intPhoneNumber)
                    .registerValidator('tinymcenotempty', tinymceNotEmpty)
                    .on('plugins.icon.set', function(e) {
                        if (e.iconElement && document.getElementById(e.element.id).parentNode) {
                            let $rightAddon = document.getElementById(e.element.id).parentNode.querySelector('[class*="phpfb-addon-after"]');
                            if (e.element.closest('.bulma-form') && e.element.closest('.field')) {
                                $rightAddon = e.element.closest('.field').querySelector('[class*="phpfb-addon-after"]:not([class*="addon-control"])');
                            }
                            if ($rightAddon) {
                                if ($rightAddon.tagName === 'I' && (e.element.closest('.bs4-form') || e.element.closest('.bs5-form'))) {
                                    $rightAddon = $rightAddon.parentNode;
                                }
                                if (e.iconElement.tagName === 'I' && e.iconElement.parentNode.classList.contains('is-right')) {
                                    // Bulma icons
                                    e.iconElement.parentNode.style.right = '24px';
                                } else if (e.element.closest('.uikit-form')) {
                                    let style = $rightAddon.currentStyle || window.getComputedStyle($rightAddon),
                                        marginLeft = 0,
                                        paddingLeft = 0;
                                    if (style.marginLeft.match(/px/)) {
                                        marginLeft += parseInt(style.marginLeft.replace('px', ''));
                                    }
                                    if (style.paddingLeft.match(/px/)) {
                                        paddingLeft += parseInt(style.paddingLeft.replace('px', ''));
                                    }

                                    if (e.element.tagName === 'SELECT') {
                                        marginLeft += 20;
                                    }

                                    e.iconElement.style.right = ($rightAddon.offsetWidth + (marginLeft + paddingLeft / 2)) - 15 + 'px';
                                } else if (e.element.closest('.foundation-form')) {
                                    e.iconElement.style.right = $rightAddon.offsetWidth + 'px';
                                } else {
                                    e.iconElement.style.right = $rightAddon.offsetWidth + 15 + 'px';
                                }
                            } else if (e.element.closest('.uikit-form') && e.element.tagName === 'SELECT') {
                                e.iconElement.style.right = '20px';
                            }
                            switch (e.status) {
                                case 'Validating':
                                    e.iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg"viewBox="0 0 512.333 512" width="16" height="16"> <path d="M440.935 12.574l3.966 82.766C399.416 41.904 331.674 8 256 8 134.813 8 33.933 94.924 12.296 209.824 10.908 217.193 16.604 224 24.103 224h49.084c5.57 0 10.377-3.842 11.676-9.259C103.407 137.408 172.931 80 256 80c60.893 0 114.512 30.856 146.104 77.801l-101.53-4.865c-6.845-.328-12.574 5.133-12.574 11.986v47.411c0 6.627 5.373 12 12 12h200.333c6.627 0 12-5.373 12-12V12c0-6.627-5.373-12-12-12h-47.411c-6.853 0-12.315 5.729-11.987 12.574zM256 432c-60.895 0-114.517-30.858-146.109-77.805l101.868 4.871c6.845.327 12.573-5.134 12.573-11.986v-47.412c0-6.627-5.373-12-12-12H12c-6.627 0-12 5.373-12 12V500c0 6.627 5.373 12 12 12h47.385c6.863 0 12.328-5.745 11.985-12.599l-4.129-82.575C112.725 470.166 180.405 504 256 504c121.187 0 222.067-86.924 243.704-201.824 1.388-7.369-4.308-14.176-11.807-14.176h-49.084c-5.57 0-10.377 3.842-11.676 9.259C408.593 374.592 339.069 432 256 432z" fill="#FF4136" /> </svg>';
                                    break;

                                case 'Invalid':
                                    e.iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg"viewBox="0 0 352 512" width="16" height="16"> <path d="M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z" fill="#F44336" /> </svg>';
                                    break;

                                case 'Valid':
                                    e.iconElement.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg"viewBox="0 0 512 512" width="16" height="16"> <path d="M173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69 432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001z" fill="#4CAF50" /> </svg>';
                                    break;

                                default:
                                    e.iconElement.innerHTML = '';
                                    break;
                            }
                        }
                    });

                // callback if function exists
                if (typeof(fvCallback) !== "undefined") {
                    setTimeout(fvCallback, 0);
                }

                if (dataAttr.fvDebug === undefined && dataAttr.fvNoAutoSubmit === undefined) {
                    form.fv.on('core.form.valid', function() {
                        if (!document.querySelector("#bd-hr_main1").classList.contains('ajax-form')) {
                            if (!document.querySelector("#bd-hr_main1").classList.contains('has-recaptcha-v3')) {
                                document.querySelector("#bd-hr_main1").submit();
                            }
                        } else {
                            var formId = document.querySelector("#bd-hr_main1").getAttribute('id'),
                                $form = document.getElementById(formId);
                            let data = new FormData($form);
                            fetch($form.getAttribute('action'), {
                                method: 'post',
                                body: new URLSearchParams(data).toString(),
                                headers: {
                                    'Content-type': 'application/x-www-form-urlencoded'
                                },
                                cache: 'no-store',
                                credentials: 'include'
                            }).then(function(response) {
                                return response.text()
                            }).then(function(data) {
                                let $formContainer = document.querySelector('*[data-ajax-form-id="' + formId + '"]');
                                $formContainer.innerHTML = '';
                                loadData(data, '#' + $formContainer.id).then(() => {
                                    window.document.dispatchEvent(loadAjaxFormEvent[formId]);
                                });
                            }).catch(function(error) {
                                console.log(error);
                            });
                        }
                    });
                }

                if (document.querySelector('#bd-hr_main1.js-badger-accordion')) {
                    form.fv.on('core.form.invalid', function() {
                        let invalidClasses = ['.is-invalid', '.is-danger', '[aria-invalid="true"]', '.is-invalid-input', '.uk-form-danger'];
                        firstInvalid = null;
                        invalidClasses.forEach(ic => {
                            if (document.querySelector('#bd-hr_main1 ' + ic) !== null) {
                                firstInvalid = document.querySelector('#bd-hr_main1 ' + ic);
                            }
                        });
                        let fieldset = firstInvalid.closest('fieldset');
                        let fieldsetIndex = fieldset.dataset.acIndex;
                        phpfbAccordion["#bd-hr_main1"].closeAll();
                        phpfbAccordion["#bd-hr_main1"].open(fieldsetIndex);
                    });
                }

                if (dataAttr.fvNoIcon !== undefined) {
                    form.fv.deregisterPlugin('icon');
                }

                if (!document.querySelector("#bd-hr_main1").classList.contains('bulma-form') || !document.querySelector("#bd-hr_main1").classList.contains('bulma-form-horizontal')) {
                    form.fv.deregisterPlugin('message');
                }

                if (document.querySelector('#' + formId + ' button[type="reset"]')) {
                    document.querySelector('#' + formId + ' button[type="reset"]').addEventListener('click', () => {
                        form.fv.resetForm(true);
                    });
                }

                if (document.querySelector('#' + formId + ' .litepick')) {
                    Array.from(document.querySelectorAll('#' + formId + ' .litepick')).forEach(element => {
                        element.addEventListener('change', function() {
                            setTimeout(() => {
                                form.fv.validateField(element.getAttribute('name'));
                                if (element.dataset && element.dataset.elementEnd) {
                                    form.fv.validateField(document.getElementById(element.dataset.elementEnd).getAttribute('name'));
                                }
                            }, 400);
                        });
                    });
                }


            };


        });
    </script>












    <!-- Bootstrap 5 JavaScript -->
    <!-- Bootstrap 5 JavaScript -->
    <!-- Bootstrap 5 JavaScript -->
    <!-- Bootstrap 5 JavaScript -->
    <!-- Bootstrap 5 JavaScript -->



    <script>
        if (typeof forms === "undefined") {
            var forms = [];
        }
        forms["bd-hr_main1"] = {};
        document.addEventListener('DOMContentLoaded', function(event) {
            if (top != self && typeof location.ancestorOrigins != "undefined") {
                if (location.ancestorOrigins[0] !== "https://preview.codecanyon.net" && !document.getElementById("drag-and-drop-preview") && document.getElementById("bd-hr_main1")) {
                    document.getElementById("bd-hr_main1").addEventListener("submit", function(e) {
                        e.preventDefault();
                        console.log("not allowed");
                        return false;
                    });
                }
            }

            if (typeof(phpfbSignPads) == "undefined") {
                window.phpfbSignPads = [];
                window.resizeSignatures = function() {
                    let ratio = Math.max(window.devicePixelRatio || 1, 1);
                    let signatures = document.querySelectorAll('.signature-pad-canvas');
                    signatures.forEach(item => {
                        if (item.getAttribute('data-percent-width')) {
                            let percent = item.getAttribute('data-percent-width') / 100;
                            item.style.width = item.parentNode.offsetWidth * percent + 'px';
                        }
                        item.width = item.offsetWidth * ratio;
                        item.height = item.offsetHeight * ratio;
                        item.getContext("2d").scale(ratio, ratio);
                    });
                    window.phpfbSignPads.forEach(function(el) {
                        el.clear();
                    })
                };
                window.addEventListener('resize', resizeSignatures);
            }

            let inputName = document.querySelector("#bd-hr_main1 #user-signature").getAttribute('name'),
                dataAttr = document.querySelector("#bd-hr_main1 #user-signature").dataset,
                dataWidth = dataAttr.width === undefined ? '100%' : dataAttr.width,
                dataHeight = dataAttr.height === undefined ? 200 : dataAttr.height,
                dataBackgroundColor = dataAttr.backgroundColor === undefined ? 'rgba(255, 255, 255, 0)' : dataAttr.backgroundColor,
                dataPenColor = dataAttr.penColor === undefined ? 'rgb(0, 0, 0)' : dataAttr.penColor,
                dataClearButton = dataAttr.clearButton === undefined ? false : dataAttr.clearButton === 'true',
                dataClearButtonClass = dataAttr.clearButtonClass === undefined ? '' : dataAttr.clearButtonClass,
                dataClearButtonText = dataAttr.clearButtonText === undefined ? 'clear' : dataAttr.clearButtonText;

            document.querySelector("#bd-hr_main1 #user-signature-canvas").height = dataHeight;
            document.querySelector("#bd-hr_main1 #user-signature-canvas").style.height = dataHeight + 'px';

            if (!isNaN(dataWidth)) {
                document.querySelector("#bd-hr_main1 #user-signature-canvas").width = dataWidth;
                document.querySelector("#bd-hr_main1 #user-signature-canvas").style.width = dataWidth + 'px';
            } else {
                // if percent
                document.querySelector("#bd-hr_main1 #user-signature-canvas").setAttribute('data-percent-width', dataWidth.replace('%', ''));
            }

            resizeSignatures();

            let signaturePad = new SignaturePad(document.querySelector("#bd-hr_main1 #user-signature-canvas"), {
                backgroundColor: dataBackgroundColor,
                penColor: dataPenColor,
                onEnd: function() {
                    let data = signaturePad.toDataURL('image/png');
                    document.querySelector("#bd-hr_main1 #user-signature").value = data;
                }
            });

            window.phpfbSignPads["#bd-hr_main1 #user-signature"] = signaturePad;

            if (dataClearButton == true) {
                let $clearButton = document.createElement('button');
                dataClearButtonClass.split(' ').forEach(cl => {
                    $clearButton.classList.add(cl);
                });
                $clearButton.classList.add('sign-pad-btn');
                $clearButton.textContent = dataClearButtonText;

                document.querySelector("#bd-hr_main1 #user-signature-canvas").insertAdjacentElement('afterend', $clearButton);

                $clearButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.querySelector("#bd-hr_main1 #user-signature").value = '';
                    window.phpfbSignPads["#bd-hr_main1 #user-signature"].clear();
                    return false;
                });
            }
            grecaptcha.ready(function() {
                var $submitBtn = document.querySelector("#bd-hr_main1 button[type='submit']");
                if ($submitBtn) {
                    document.querySelector('input[name="g-recaptcha-response"]').value = '';
                    if (document.querySelector("#bd-hr_main1").classList.contains('has-validator')) {
                        // if formvalidation enabled

                        var formId = document.querySelector("#bd-hr_main1").getAttribute('id');
                        var form = forms[formId];

                        document.querySelector("#bd-hr_main1").classList.add('has-recaptcha-v3')

                        $submitBtn.addEventListener('click', function(e) {
                            e.preventDefault;
                            form.fv.validate()
                                .then(function(status) {
                                    if (status == 'Valid') {
                                        grecaptcha.execute('6LeNWaQUAAAAAGO_c1ORq2wla-PEFlJruMzyH5L6', {
                                            action: 'default'
                                        }).then(function(token) {
                                            document.querySelector('input[name="g-recaptcha-response"]').value = token;
                                            document.querySelector("#bd-hr_main1").submit();
                                        });
                                    }
                                    return false;
                                });
                            return false;
                        });
                    } else {
                        $submitBtn.addEventListener('click', function(e) {
                            e.preventDefault;
                            grecaptcha.execute('6LeNWaQUAAAAAGO_c1ORq2wla-PEFlJruMzyH5L6', {
                                action: 'default'
                            }).then(function(token) {
                                document.querySelector('input[name="g-recaptcha-response"]').value = token;
                                document.querySelector("#bd-hr_main1").submit();
                            });
                            return false;
                        });
                    }
                } else {
                    const alert = document.createElement("p");
                    alert.classList.add("alert");
                    alert.classList.add("alert-danger");
                    alert.innerHTML = 'Recaptcha V3 - no submit button found';
                    const parent = document.querySelector("#bd-hr_main1").parentNode;
                    parent.insertBefore(alert, document.querySelector("#bd-hr_main1"));
                }
            });
            if (typeof(l) == "undefined") {
                window.l = [];
            }
            var $laddaForm = document.querySelector("#bd-hr_main1 button[name='submit-btn']").closest('form'),
                formId = $laddaForm.getAttribute('id'),
                form = forms[formId];

            if (document.querySelector("#bd-hr_main1 button[name='submit-btn']").getAttribute('data-style') === null) {
                document.querySelector("#bd-hr_main1 button[name='submit-btn']").setAttribute('data-style', 'zoom-in');
            }

            l["#bd-hr_main1 button[name='submit-btn']"] = Ladda.create(document.querySelector("#bd-hr_main1 button[name='submit-btn']"));

            document.querySelector("#bd-hr_main1 button[name='submit-btn']").addEventListener('click', function(e) {
                if (!e.target.closest('button').disabled) {
                    if (!document.querySelector("#bd-hr_main1 button[name='submit-btn']").hasAttribute('data-loading')) {
                        l["#bd-hr_main1 button[name='submit-btn']"].start();

                        // formValidation won't work if submit button is disabled
                        e.target.closest('button').removeAttribute('disabled');
                    } else {
                        l["#bd-hr_main1 button[name='submit-btn']"].stop();
                    }
                }

                // stop if validation fails
                if (typeof(form.fv) == 'object') {
                    form.fv.on('core.form.invalid', function() {
                        l["#bd-hr_main1 button[name='submit-btn']"].stop();
                    });
                }
            });
          












        });
    </script>
    <!-- Button trigger modal -->


    <!-- ============================================-->
    <!-- <section> begin ============================-->



    <?PHP
    $nofa = true;
    include($_SERVER['DOCUMENT_ROOT'] . '/core/'.$website['ui_version'].'/footer2.inc');
