-- =============================================================
-- HRM — طرحواره پایگاه داده SQLite
-- سامانه مدیریت منابع انسانی (نسخه 1.0.0)
-- =============================================================

PRAGMA foreign_keys = ON;

-- ---------- تنظیمات ----------
CREATE TABLE IF NOT EXISTS settings (
    id     INTEGER PRIMARY KEY AUTOINCREMENT,
    k      TEXT NOT NULL UNIQUE,
    v      TEXT
);

-- ---------- نقش‌ها و مجوزها ----------
CREATE TABLE IF NOT EXISTS roles (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    slug        TEXT NOT NULL UNIQUE,
    description TEXT,
    is_system   INTEGER NOT NULL DEFAULT 0,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS permissions (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    slug       TEXT NOT NULL UNIQUE,
    title      TEXT NOT NULL,
    grp        TEXT NOT NULL DEFAULT 'عمومی',
    sort       INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id    INTEGER NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission TEXT NOT NULL,
    PRIMARY KEY (role_id, permission)
);

-- ---------- کاربران ----------
CREATE TABLE IF NOT EXISTS users (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id      INTEGER REFERENCES employees(id) ON DELETE SET NULL,
    username         TEXT NOT NULL UNIQUE,
    email            TEXT,
    password_hash    TEXT NOT NULL,
    role_id          INTEGER REFERENCES roles(id) ON DELETE SET NULL,
    status           TEXT NOT NULL DEFAULT 'active',      -- active|inactive
    failed_attempts  INTEGER NOT NULL DEFAULT 0,
    locked_until     TEXT,
    last_login_at    TEXT,
    must_change_pwd  INTEGER NOT NULL DEFAULT 0,
    created_at       TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT
);

CREATE TABLE IF NOT EXISTS user_sessions (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id   TEXT NOT NULL,
    ip_address   TEXT,
    user_agent   TEXT,
    created_at   TEXT NOT NULL,
    last_seen_at TEXT NOT NULL,
    revoked_at   TEXT
);
CREATE INDEX IF NOT EXISTS idx_user_sessions_sid ON user_sessions(session_id);

-- ---------- ساختار سازمانی ----------
CREATE TABLE IF NOT EXISTS departments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    code        TEXT,
    parent_id   INTEGER REFERENCES departments(id) ON DELETE SET NULL,
    manager_id  INTEGER,                          -- employee id
    description TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS positions (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    title       TEXT NOT NULL,
    code        TEXT,
    level       INTEGER NOT NULL DEFAULT 1,        -- 1=کارشناس ... 5=مدیرعامل
    department_id INTEGER REFERENCES departments(id) ON DELETE SET NULL,
    description TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------- کارمندان ----------
CREATE TABLE IF NOT EXISTS employees (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    emp_no         TEXT NOT NULL UNIQUE,
    first_name     TEXT NOT NULL,
    last_name      TEXT NOT NULL,
    national_code  TEXT,
    gender         TEXT DEFAULT 'male',            -- male|female
    birth_date     TEXT,                            -- میلادی
    hire_date      TEXT,                            -- میلادی
    dept_id        INTEGER REFERENCES departments(id) ON DELETE SET NULL,
    position_id    INTEGER REFERENCES positions(id) ON DELETE SET NULL,
    manager_id     INTEGER REFERENCES employees(id) ON DELETE SET NULL,
    shift_id       INTEGER,                         -- به جای FK تا چرخه ایجاد نشود
    email          TEXT,
    phone          TEXT,
    address        TEXT,
    photo          TEXT,
    contract_type  TEXT DEFAULT 'permanent',        -- permanent|temporary|probation|project
    contract_start TEXT,
    contract_end   TEXT,
    probation_end  TEXT,
    salary_base    REAL NOT NULL DEFAULT 0,
    bank_account   TEXT,
    insurance_no   TEXT,
    education      TEXT,
    marital_status TEXT DEFAULT 'single',
    dependents     INTEGER NOT NULL DEFAULT 0,
    work_days      TEXT DEFAULT '6,0,1,2,3,4',     -- روزهای کاری (0=یکشنبه...6=شنبه)
    status         TEXT NOT NULL DEFAULT 'active', -- active|inactive|archived
    notes          TEXT,
    created_at     TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at     TEXT,
    deleted_at     TEXT                             -- حذف نرم
);
CREATE INDEX IF NOT EXISTS idx_emp_status ON employees(status);
CREATE INDEX IF NOT EXISTS idx_emp_dept ON employees(dept_id);

CREATE TABLE IF NOT EXISTS work_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    title       TEXT NOT NULL,
    department  TEXT,
    from_date   TEXT,
    to_date     TEXT,
    note        TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS documents (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    title       TEXT NOT NULL,
    category    TEXT DEFAULT 'other',              -- id|contract|education|medical|other
    file_path   TEXT,
    expiry_date TEXT,
    note        TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS employee_notes (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    user_id     INTEGER,
    note        TEXT NOT NULL,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------- مرخصی، تعطیلات، شیفت ----------
CREATE TABLE IF NOT EXISTS leave_types (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT NOT NULL,
    slug         TEXT,
    paid         INTEGER NOT NULL DEFAULT 1,       -- استحقاقی/با حقوق؟
    deduct_pay   INTEGER NOT NULL DEFAULT 0,       -- کسر از حقوق؟
    color        TEXT DEFAULT '#2563eb',
    annual_days  REAL NOT NULL DEFAULT 30,
    active       INTEGER NOT NULL DEFAULT 1,
    sort         INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS leave_requests (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id  INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    type_id      INTEGER NOT NULL REFERENCES leave_types(id),
    from_date    TEXT NOT NULL,                    -- میلادی
    to_date      TEXT NOT NULL,
    days         REAL NOT NULL DEFAULT 1,
    half_day     INTEGER NOT NULL DEFAULT 0,
    reason       TEXT,
    status       TEXT NOT NULL DEFAULT 'pending',  -- pending|approved|rejected|canceled
    approver_id  INTEGER,
    decided_at   TEXT,
    decide_note  TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_leave_status ON leave_requests(status);

CREATE TABLE IF NOT EXISTS leave_balances (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    type_id     INTEGER NOT NULL REFERENCES leave_types(id),
    year        INTEGER NOT NULL,
    days_total  REAL NOT NULL DEFAULT 0,
    days_used   REAL NOT NULL DEFAULT 0,
    UNIQUE (employee_id, type_id, year)
);

CREATE TABLE IF NOT EXISTS holidays (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    date       TEXT NOT NULL UNIQUE,               -- میلادی
    title      TEXT NOT NULL,
    national   INTEGER NOT NULL DEFAULT 1,
    recurring  INTEGER NOT NULL DEFAULT 0          -- تکرار سالانه (فقط ماه/روز)
);

CREATE TABLE IF NOT EXISTS shifts (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    name       TEXT NOT NULL,
    start_time TEXT NOT NULL DEFAULT '08:00',
    end_time   TEXT NOT NULL DEFAULT '17:00',
    grace_min  INTEGER NOT NULL DEFAULT 10,        -- مهلت تاخیر
    overnight  INTEGER NOT NULL DEFAULT 0,
    active     INTEGER NOT NULL DEFAULT 1
);

-- ---------- حضور و غیاب ----------
CREATE TABLE IF NOT EXISTS attendance (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    date        TEXT NOT NULL,                     -- میلادی
    check_in    TEXT,
    check_out   TEXT,
    status      TEXT NOT NULL DEFAULT 'present',   -- present|late|absent|leave|holiday|remote|trip
    late_min    INTEGER NOT NULL DEFAULT 0,
    early_min   INTEGER NOT NULL DEFAULT 0,
    work_min    INTEGER NOT NULL DEFAULT 0,
    source      TEXT DEFAULT 'manual',             -- manual|import|kiosk
    note        TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (employee_id, date)
);
CREATE INDEX IF NOT EXISTS idx_att_date ON attendance(date);

CREATE TABLE IF NOT EXISTS overtime_requests (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    date        TEXT NOT NULL,
    hours       REAL NOT NULL DEFAULT 1,
    reason      TEXT,
    multiplier  REAL NOT NULL DEFAULT 1.4,
    status      TEXT NOT NULL DEFAULT 'pending',   -- pending|approved|rejected
    approver_id INTEGER,
    decided_at  TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------- حقوق و دستمزد ----------
CREATE TABLE IF NOT EXISTS payroll_periods (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    year       INTEGER NOT NULL,
    month      INTEGER NOT NULL,                   -- 1..12 شمسی
    status     TEXT NOT NULL DEFAULT 'draft',      -- draft|locked|paid
    note       TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now')),
    locked_at  TEXT,
    paid_at    TEXT,
    UNIQUE (year, month)
);

CREATE TABLE IF NOT EXISTS salary_components (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    code          TEXT NOT NULL UNIQUE,
    name          TEXT NOT NULL,
    type          TEXT NOT NULL DEFAULT 'earning', -- earning|deduction
    calc_type     TEXT NOT NULL DEFAULT 'fixed',   -- fixed|percent_base|formula|employee
    formula       TEXT,                            -- مثلاً base*0.10 یا percent:10
    default_amount REAL NOT NULL DEFAULT 0,
    taxable       INTEGER NOT NULL DEFAULT 0,
    pension_rate  REAL NOT NULL DEFAULT 0,         -- سهم بیمه کارمند (٪)
    insurance_rate REAL NOT NULL DEFAULT 0,
    tax_bracket   INTEGER NOT NULL DEFAULT 0,      -- جزء مالیات پلکانی؟
    active        INTEGER NOT NULL DEFAULT 1,
    sort          INTEGER NOT NULL DEFAULT 0,
    is_system     INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS employee_components (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id  INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    component_id INTEGER NOT NULL REFERENCES salary_components(id) ON DELETE CASCADE,
    amount       REAL NOT NULL DEFAULT 0,
    UNIQUE (employee_id, component_id)
);

CREATE TABLE IF NOT EXISTS payroll_lines (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    period_id    INTEGER NOT NULL REFERENCES payroll_periods(id) ON DELETE CASCADE,
    employee_id  INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    component_id INTEGER NOT NULL REFERENCES salary_components(id) ON DELETE CASCADE,
    amount       REAL NOT NULL DEFAULT 0,
    UNIQUE (period_id, employee_id, component_id)
);

CREATE TABLE IF NOT EXISTS payslips (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    period_id     INTEGER NOT NULL REFERENCES payroll_periods(id) ON DELETE CASCADE,
    employee_id   INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    base_salary   REAL NOT NULL DEFAULT 0,
    gross_earn    REAL NOT NULL DEFAULT 0,
    total_deduct  REAL NOT NULL DEFAULT 0,
    tax           REAL NOT NULL DEFAULT 0,
    net_pay       REAL NOT NULL DEFAULT 0,
    worked_days   REAL NOT NULL DEFAULT 0,
    absent_days   REAL NOT NULL DEFAULT 0,
    details_json  TEXT,
    paid_at       TEXT,
    created_at    TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (period_id, employee_id)
);

CREATE TABLE IF NOT EXISTS loans (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id   INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    amount        REAL NOT NULL,
    installment   REAL NOT NULL,
    months        INTEGER NOT NULL,
    paid_installments INTEGER NOT NULL DEFAULT 0,
    start_date    TEXT,
    reason        TEXT,
    status        TEXT NOT NULL DEFAULT 'active',  -- active|finished|canceled
    created_at    TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS loan_payments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    loan_id     INTEGER NOT NULL REFERENCES loans(id) ON DELETE CASCADE,
    amount      REAL NOT NULL,
    paid_date   TEXT,
    note        TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS rewards_penalties (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    kind        TEXT NOT NULL,                     -- bonus|penalty
    amount      REAL NOT NULL,
    date        TEXT,
    reason      TEXT,
    period_id   INTEGER REFERENCES payroll_periods(id) ON DELETE SET NULL,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------- درخواست‌ها و گردش کار ----------
CREATE TABLE IF NOT EXISTS requests (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id  INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    type         TEXT NOT NULL DEFAULT 'general',  -- general|suggestion|issue|equipment|other
    title        TEXT NOT NULL,
    body         TEXT,
    status       TEXT NOT NULL DEFAULT 'pending',  -- pending|approved|rejected
    approver_id  INTEGER,
    decided_at   TEXT,
    decide_note  TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS notifications (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title      TEXT NOT NULL,
    body       TEXT,
    link       TEXT,
    read_at    TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_notif_user ON notifications(user_id, read_at);

CREATE TABLE IF NOT EXISTS activity_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER,
    level      TEXT NOT NULL DEFAULT 'info',       -- info|warning|error
    action     TEXT NOT NULL,
    message    TEXT,
    context    TEXT,
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);
CREATE INDEX IF NOT EXISTS idx_log_action ON activity_log(action);

-- ---------- استخدام (ATS) ----------
CREATE TABLE IF NOT EXISTS jobs (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    title        TEXT NOT NULL,
    dept_id      INTEGER REFERENCES departments(id) ON DELETE SET NULL,
    description  TEXT,
    requirements TEXT,
    salary_min   REAL,
    salary_max   REAL,
    openings     INTEGER NOT NULL DEFAULT 1,
    status       TEXT NOT NULL DEFAULT 'open',     -- open|paused|closed
    published_at TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS applicants (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    job_id        INTEGER REFERENCES jobs(id) ON DELETE SET NULL,
    full_name     TEXT NOT NULL,
    email         TEXT,
    phone         TEXT,
    stage         TEXT NOT NULL DEFAULT 'new',     -- new|screening|interview|offer|hired|rejected
    expected_pay  REAL,
    resume_path   TEXT,
    source        TEXT,
    notes         TEXT,
    created_at    TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT
);

CREATE TABLE IF NOT EXISTS interviews (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    applicant_id   INTEGER NOT NULL REFERENCES applicants(id) ON DELETE CASCADE,
    interviewer_id INTEGER,
    interview_at   TEXT,
    mode           TEXT DEFAULT 'in_person',       -- in_person|online|phone
    score          INTEGER,
    result         TEXT,                           -- pending|pass|fail
    notes          TEXT,
    created_at     TEXT NOT NULL DEFAULT (datetime('now'))
);

-- ---------- آموزش ----------
CREATE TABLE IF NOT EXISTS courses (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    title      TEXT NOT NULL,
    provider   TEXT,
    start_date TEXT,
    end_date   TEXT,
    seats      INTEGER NOT NULL DEFAULT 20,
    cost       REAL NOT NULL DEFAULT 0,
    status     TEXT NOT NULL DEFAULT 'planned',    -- planned|running|finished|canceled
    note       TEXT,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS enrollments (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id   INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    status      TEXT NOT NULL DEFAULT 'enrolled',  -- enrolled|completed|dropped
    score       REAL,
    cert_no     TEXT,
    UNIQUE (course_id, employee_id)
);

-- ---------- عملکرد ----------
CREATE TABLE IF NOT EXISTS goals (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id  INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    title        TEXT NOT NULL,
    description  TEXT,
    weight       REAL NOT NULL DEFAULT 25,         -- وزن در ارزیابی (٪)
    period       TEXT,                             -- مثلاً 1403-نیمه اول
    target_value TEXT,
    actual_value TEXT,
    score        REAL DEFAULT 0,                   -- 0..100
    status       TEXT NOT NULL DEFAULT 'active',   -- active|done|cancelled
    due_date     TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS reviews (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id  INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    reviewer_id  INTEGER,
    period       TEXT NOT NULL,
    overall_score REAL NOT NULL DEFAULT 0,
    strengths    TEXT,
    improvements TEXT,
    status       TEXT NOT NULL DEFAULT 'draft',    -- draft|submitted
    submitted_at TEXT,
    created_at   TEXT NOT NULL DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS discipline_records (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id INTEGER NOT NULL REFERENCES employees(id) ON DELETE CASCADE,
    date        TEXT,
    kind        TEXT NOT NULL DEFAULT 'warning',   -- warning|violation|commendation
    title       TEXT NOT NULL,
    description TEXT,
    action      TEXT,
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
);
