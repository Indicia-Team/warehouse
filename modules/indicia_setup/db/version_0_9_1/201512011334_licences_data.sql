INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
VALUES (
    'Creative Commons Attribution',
    'CC-BY',
    'This licence lets others distribute, remix, tweak, and build upon your work, even commercially, as long as they credit ' ||
       'you for the original creation. This is the most accommodating of Creative Commons licences offered. Recommended for ' ||
       'maximum dissemination and use of licenced materials.',
    'https://creativecommons.org/licenses/by/4.0',
    'https://creativecommons.org/licenses/by/4.0/legalcode',
    '4.0',
    now(), 1, now(), 1
);

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
VALUES (
    'Creative Commons Attribution-NonCommercial',
    'CC-BY-NC',
    'This licence lets others remix, tweak, and build upon your work non-commercially, and although their new works must ' ||
        'also acknowledge you and be non-commercial, they don’t have to licence their derivative works on the same terms.',
    'https://creativecommons.org/licenses/by-nc/4.0',
    'https://creativecommons.org/licenses/by-nc/4.0/legalcode',
    '4.0',
    now(), 1, now(), 1
);

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
VALUES (
    'Creative Commons "No Rights Reserved"',
    'CC0',
    'Copyright and other laws throughout the world automatically extend copyright protection to works of authorship and ' ||
        'databases, whether the author or creator wants those rights or not. CC0 gives those who want to give up those ' ||
        'rights a way to do so, to the fullest extent allowed by law. Once the creator or a subsequent owner of a work ' ||
        'applies CC0 to a work, the work is no longer his or hers in any meaningful sense under copyright law. Anyone can ' ||
        'then use the work in any way and for any purpose, including commercial purposes, subject to other laws and the ' ||
        'rights others may have in the work or how the work is used. Think of CC0 as the "no rights reserved" option.',
    'https://creativecommons.org/publicdomain/zero/1.0',
    'https://creativecommons.org/publicdomain/zero/1.0/legalcode',
    '1.0',
    now(), 1, now(), 1
);

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
VALUES (
    'Open Government licence (UK)',
    'OGL',
    'Open Government Licence for public sector information in the UK.',
    'http://www.nationalarchives.gov.uk/doc/open-government-licence/version/3',
    'http://www.nationalarchives.gov.uk/doc/open-government-licence/version/3',
    '3',
    now(), 1, now(), 1
);
