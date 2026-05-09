UPDATE sdopang1_roles
SET role_name = 'Employee',
    role_description = 'Employee personal submissions and leave access'
WHERE role_id = 4;

INSERT INTO sdopang1_roles (role_id, role_name, role_description)
VALUES
    (5, 'District Supervisor', 'District-level user'),
    (6, 'Chief', 'Division chief user'),
    (7, 'Unit Head', 'Office or unit head user')
ON DUPLICATE KEY UPDATE
    role_name = VALUES(role_name),
    role_description = VALUES(role_description);
