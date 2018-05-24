-- Add licences to support wider range in use on iNat.

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
SELECT
    'Creative Commons Attribution-ShareAlike',
    'CC-BY-SA',
    'This license lets others remix, tweak, and build upon your work, even for a profit, as long as they credit you ' ||
      'for the original creation, and also license their new creations under identical licensing terms.',
    'https://creativecommons.org/licenses/by-sa/4.0',
    'https://creativecommons.org/licenses/by-sa/4.0/legalcode',
    '4.0',
    now(), 1, now(), 1
WHERE NOT EXISTS (SELECT id FROM licences WHERE code='CC-BY-SA');

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
SELECT
    'Creative Commons Attribution-NoDerivatives',
    'CC-BY-ND',
    'This license lets others redistribute, for both profitable and non-commercial purposes, as long as it is ' ||
      'passed along unchanged and in whole, with credit attributed to you.',
    'https://creativecommons.org/licenses/by-nd/4.0',
    'https://creativecommons.org/licenses/by-nd/4.0/legalcode',
    '4.0',
    now(), 1, now(), 1
WHERE NOT EXISTS (SELECT id FROM licences WHERE code='CC-BY-ND');

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
SELECT
    'Creative Commons Attribution-NonCommercial-ShareAlike ',
    'CC-BY-NC-SA',
    'This license allows others to remix, tweak, and build upon your work non-commercially, as long as they give ' ||
      'credit to you and license their new creations under identical terms.',
    'https://creativecommons.org/licenses/by-nc-sa/4.0',
    'https://creativecommons.org/licenses/by-nc-sa/4.0/legalcode',
    '4.0',
    now(), 1, now(), 1
WHERE NOT EXISTS (SELECT id FROM licences WHERE code='CC-BY-NC-SA');

INSERT INTO licences(
    title,
    code,
    description,
    url_readable,
    url_legal,
    version,
    created_on, created_by_id, updated_on, updated_by_id
)
SELECT
    'Creative Commons Attribution-NonCommercial-NoDerivs',
    'CC-BY-NC-ND',
    'This licence only allows others to download works and share them as long as the creator is credited. They may ' ||
      'not be changed in any way, or used for commercial purposes.',
    'https://creativecommons.org/licenses/by-nc-nd/4.0',
    'https://creativecommons.org/licenses/by-nc-nd/4.0/legalcode',
    '4.0',
    now(), 1, now(), 1
WHERE NOT EXISTS (SELECT id FROM licences WHERE code='CC-BY-NC-ND');
