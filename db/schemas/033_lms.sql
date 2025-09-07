-- TPT Open ERP - Learning Management System Schema
-- Migration: 033
-- Description: LMS tables for courses, enrollments, and certifications

-- Courses Table
CREATE TABLE IF NOT EXISTS courses (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    course_code VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    category VARCHAR(100),
    level VARCHAR(20) DEFAULT 'beginner',
    language VARCHAR(10) DEFAULT 'en',
    duration_hours INTEGER,
    max_students INTEGER,
    enrollment_deadline DATE,
    start_date DATE,
    end_date DATE,
    price DECIMAL(10,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    instructor_id INTEGER REFERENCES users(id),
    status VARCHAR(50) DEFAULT 'draft',
    is_published BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    thumbnail_url TEXT,
    video_url TEXT,
    prerequisites TEXT,
    learning_objectives TEXT[],
    tags TEXT[],
    difficulty_rating DECIMAL(3,2),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT courses_level_check CHECK (level IN ('beginner', 'intermediate', 'advanced', 'expert')),
    CONSTRAINT courses_status_check CHECK (status IN ('draft', 'published', 'archived', 'cancelled'))
);

-- Course Modules Table
CREATE TABLE IF NOT EXISTS course_modules (
    id SERIAL PRIMARY KEY,
    course_id INTEGER NOT NULL REFERENCES courses(id) ON DELETE CASCADE,
    module_order INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    duration_minutes INTEGER,
    is_mandatory BOOLEAN DEFAULT TRUE,
    content_type VARCHAR(50) DEFAULT 'lesson',
    content_url TEXT,
    video_url TEXT,
    document_url TEXT,
    quiz_id INTEGER,
    assignment_id INTEGER,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT course_modules_content_type_check CHECK (content_type IN ('lesson', 'video', 'document', 'quiz', 'assignment', 'discussion'))
);

-- Course Enrollments Table
CREATE TABLE IF NOT EXISTS course_enrollments (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    course_id INTEGER NOT NULL REFERENCES courses(id),
    student_id INTEGER NOT NULL REFERENCES users(id),
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completion_date TIMESTAMP NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'enrolled',
    payment_status VARCHAR(50) DEFAULT 'pending',
    payment_amount DECIMAL(10,2),
    payment_date TIMESTAMP NULL,
    certificate_issued BOOLEAN DEFAULT FALSE,
    certificate_number VARCHAR(100),
    grade VARCHAR(5),
    score DECIMAL(5,2),
    notes TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT course_enrollments_status_check CHECK (status IN ('enrolled', 'in_progress', 'completed', 'dropped', 'expired')),
    CONSTRAINT course_enrollments_payment_check CHECK (payment_status IN ('pending', 'paid', 'refunded', 'cancelled')),
    UNIQUE(course_id, student_id)
);

-- Course Progress Table
CREATE TABLE IF NOT EXISTS course_progress (
    id SERIAL PRIMARY KEY,
    enrollment_id INTEGER NOT NULL REFERENCES course_enrollments(id) ON DELETE CASCADE,
    module_id INTEGER NOT NULL REFERENCES course_modules(id),
    completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    time_spent_minutes INTEGER DEFAULT 0,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    score DECIMAL(5,2),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE(enrollment_id, module_id)
);

-- Certifications Table
CREATE TABLE IF NOT EXISTS certifications (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    certification_number VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    issuing_authority VARCHAR(255) NOT NULL,
    course_id INTEGER REFERENCES courses(id),
    student_id INTEGER NOT NULL REFERENCES users(id),
    issue_date DATE NOT NULL,
    expiry_date DATE,
    status VARCHAR(50) DEFAULT 'active',
    certificate_url TEXT,
    verification_code VARCHAR(100) UNIQUE,
    skills_acquired TEXT[],
    grade VARCHAR(5),
    score DECIMAL(5,2),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT certifications_status_check CHECK (status IN ('active', 'expired', 'revoked', 'suspended'))
);

-- Quizzes Table
CREATE TABLE IF NOT EXISTS quizzes (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    course_id INTEGER REFERENCES courses(id),
    module_id INTEGER REFERENCES course_modules(id),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    time_limit_minutes INTEGER,
    passing_score DECIMAL(5,2) DEFAULT 70.00,
    max_attempts INTEGER DEFAULT 3,
    is_mandatory BOOLEAN DEFAULT TRUE,
    status VARCHAR(50) DEFAULT 'active',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT quizzes_status_check CHECK (status IN ('active', 'inactive', 'archived'))
);

-- Quiz Questions Table
CREATE TABLE IF NOT EXISTS quiz_questions (
    id SERIAL PRIMARY KEY,
    quiz_id INTEGER NOT NULL REFERENCES quizzes(id) ON DELETE CASCADE,
    question_order INTEGER NOT NULL,
    question_type VARCHAR(50) NOT NULL,
    question_text TEXT NOT NULL,
    options TEXT[], -- For multiple choice
    correct_answer TEXT,
    explanation TEXT,
    points DECIMAL(5,2) DEFAULT 1.00,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT quiz_questions_type_check CHECK (question_type IN ('multiple_choice', 'true_false', 'short_answer', 'essay'))
);

-- Quiz Attempts Table
CREATE TABLE IF NOT EXISTS quiz_attempts (
    id SERIAL PRIMARY KEY,
    quiz_id INTEGER NOT NULL REFERENCES quizzes(id),
    enrollment_id INTEGER NOT NULL REFERENCES course_enrollments(id),
    attempt_number INTEGER NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    score DECIMAL(5,2),
    passed BOOLEAN,
    answers JSONB,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE(quiz_id, enrollment_id, attempt_number)
);

-- Assignments Table
CREATE TABLE IF NOT EXISTS assignments (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    course_id INTEGER REFERENCES courses(id),
    module_id INTEGER REFERENCES course_modules(id),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructions TEXT,
    due_date DATE,
    max_points DECIMAL(5,2) DEFAULT 100.00,
    submission_type VARCHAR(50) DEFAULT 'file',
    is_mandatory BOOLEAN DEFAULT TRUE,
    allow_late_submission BOOLEAN DEFAULT FALSE,
    late_penalty DECIMAL(5,2) DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT assignments_submission_type_check CHECK (submission_type IN ('file', 'text', 'url', 'mixed'))
);

-- Assignment Submissions Table
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id SERIAL PRIMARY KEY,
    assignment_id INTEGER NOT NULL REFERENCES assignments(id),
    enrollment_id INTEGER NOT NULL REFERENCES course_enrollments(id),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submission_content TEXT,
    file_url TEXT,
    file_name VARCHAR(255),
    file_size INTEGER,
    grade DECIMAL(5,2),
    feedback TEXT,
    graded_at TIMESTAMP NULL,
    graded_by INTEGER REFERENCES users(id),

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE(assignment_id, enrollment_id)
);

-- Course Categories Table
CREATE TABLE IF NOT EXISTS course_categories (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_category_id INTEGER REFERENCES course_categories(id),
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INTEGER DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Course Reviews Table
CREATE TABLE IF NOT EXISTS course_reviews (
    id SERIAL PRIMARY KEY,
    course_id INTEGER NOT NULL REFERENCES courses(id),
    student_id INTEGER NOT NULL REFERENCES users(id),
    rating INTEGER NOT NULL,
    review_text TEXT,
    is_anonymous BOOLEAN DEFAULT FALSE,
    helpful_votes INTEGER DEFAULT 0,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT course_reviews_rating_check CHECK (rating >= 1 AND rating <= 5),
    UNIQUE(course_id, student_id)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_courses_uuid ON courses(uuid);
CREATE INDEX IF NOT EXISTS idx_courses_code ON courses(course_code);
CREATE INDEX IF NOT EXISTS idx_courses_category ON courses(category);
CREATE INDEX IF NOT EXISTS idx_courses_instructor ON courses(instructor_id);
CREATE INDEX IF NOT EXISTS idx_courses_status ON courses(status);

CREATE INDEX IF NOT EXISTS idx_course_modules_course ON course_modules(course_id);

CREATE INDEX IF NOT EXISTS idx_course_enrollments_course ON course_enrollments(course_id);
CREATE INDEX IF NOT EXISTS idx_course_enrollments_student ON course_enrollments(student_id);
CREATE INDEX IF NOT EXISTS idx_course_enrollments_status ON course_enrollments(status);

CREATE INDEX IF NOT EXISTS idx_course_progress_enrollment ON course_progress(enrollment_id);

CREATE INDEX IF NOT EXISTS idx_certifications_student ON certifications(student_id);
CREATE INDEX IF NOT EXISTS idx_certifications_course ON certifications(course_id);

CREATE INDEX IF NOT EXISTS idx_quizzes_course ON quizzes(course_id);

CREATE INDEX IF NOT EXISTS idx_quiz_questions_quiz ON quiz_questions(quiz_id);

CREATE INDEX IF NOT EXISTS idx_quiz_attempts_quiz ON quiz_attempts(quiz_id);
CREATE INDEX IF NOT EXISTS idx_quiz_attempts_enrollment ON quiz_attempts(enrollment_id);

CREATE INDEX IF NOT EXISTS idx_assignments_course ON assignments(course_id);

CREATE INDEX IF NOT EXISTS idx_assignment_submissions_assignment ON assignment_submissions(assignment_id);

CREATE INDEX IF NOT EXISTS idx_course_categories_parent ON course_categories(parent_category_id);

CREATE INDEX IF NOT EXISTS idx_course_reviews_course ON course_reviews(course_id);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_courses_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_courses_updated_at BEFORE UPDATE ON courses
    FOR EACH ROW EXECUTE FUNCTION update_courses_updated_at();

CREATE OR REPLACE FUNCTION update_course_modules_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_course_modules_updated_at BEFORE UPDATE ON course_modules
    FOR EACH ROW EXECUTE FUNCTION update_course_modules_updated_at();

CREATE OR REPLACE FUNCTION update_course_enrollments_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_course_enrollments_updated_at BEFORE UPDATE ON course_enrollments
    FOR EACH ROW EXECUTE FUNCTION update_course_enrollments_updated_at();

CREATE OR REPLACE FUNCTION update_course_progress_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_course_progress_updated_at BEFORE UPDATE ON course_progress
    FOR EACH ROW EXECUTE FUNCTION update_course_progress_updated_at();

CREATE OR REPLACE FUNCTION update_certifications_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_certifications_updated_at BEFORE UPDATE ON certifications
    FOR EACH ROW EXECUTE FUNCTION update_certifications_updated_at();

CREATE OR REPLACE FUNCTION update_quizzes_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quizzes_updated_at BEFORE UPDATE ON quizzes
    FOR EACH ROW EXECUTE FUNCTION update_quizzes_updated_at();

CREATE OR REPLACE FUNCTION update_quiz_questions_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quiz_questions_updated_at BEFORE UPDATE ON quiz_questions
    FOR EACH ROW EXECUTE FUNCTION update_quiz_questions_updated_at();

CREATE OR REPLACE FUNCTION update_quiz_attempts_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_quiz_attempts_updated_at BEFORE UPDATE ON quiz_attempts
    FOR EACH ROW EXECUTE FUNCTION update_quiz_attempts_updated_at();

CREATE OR REPLACE FUNCTION update_assignments_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_assignments_updated_at BEFORE UPDATE ON assignments
    FOR EACH ROW EXECUTE FUNCTION update_assignments_updated_at();

CREATE OR REPLACE FUNCTION update_assignment_submissions_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_assignment_submissions_updated_at BEFORE UPDATE ON assignment_submissions
    FOR EACH ROW EXECUTE FUNCTION update_assignment_submissions_updated_at();

CREATE OR REPLACE FUNCTION update_course_categories_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_course_categories_updated_at BEFORE UPDATE ON course_categories
    FOR EACH ROW EXECUTE FUNCTION update_course_categories_updated_at();

CREATE OR REPLACE FUNCTION update_course_reviews_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_course_reviews_updated_at BEFORE UPDATE ON course_reviews
    FOR EACH ROW EXECUTE FUNCTION update_course_reviews_updated_at();

-- Comments
COMMENT ON TABLE courses IS 'Courses offered in the LMS';
COMMENT ON TABLE course_modules IS 'Modules within courses';
COMMENT ON TABLE course_enrollments IS 'Student enrollments in courses';
COMMENT ON TABLE course_progress IS 'Progress tracking for enrolled students';
COMMENT ON TABLE certifications IS 'Certificates issued to students';
COMMENT ON TABLE quizzes IS 'Quizzes and assessments';
COMMENT ON TABLE quiz_questions IS 'Questions within quizzes';
COMMENT ON TABLE quiz_attempts IS 'Student attempts at quizzes';
COMMENT ON TABLE assignments IS 'Course assignments';
COMMENT ON TABLE assignment_submissions IS 'Student submissions for assignments';
COMMENT ON TABLE course_categories IS 'Categories for organizing courses';
COMMENT ON TABLE course_reviews IS 'Student reviews and ratings for courses';
