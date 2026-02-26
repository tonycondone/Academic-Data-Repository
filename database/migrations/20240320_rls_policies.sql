-- Supabase RLS Policies for Academic Data Repository
-- Enabling RLS on tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE datasets ENABLE ROW LEVEL SECURITY;
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;
ALTER TABLE downloads ENABLE ROW LEVEL SECURITY;

-- USERS Policies
CREATE POLICY "Public users are viewable by everyone."
ON users FOR SELECT
USING (true);

CREATE POLICY "Users can update their own data."
ON users FOR UPDATE
USING (auth.uid() = id);

-- DATASETS Policies
CREATE POLICY "Datasets are viewable by everyone."
ON datasets FOR SELECT
USING (is_active = true);

CREATE POLICY "Authenticated users can upload datasets."
ON datasets FOR INSERT
TO authenticated
WITH CHECK (true);

CREATE POLICY "Users can update their own datasets."
ON datasets FOR UPDATE
USING (auth.uid() = uploaded_by);

CREATE POLICY "Users can delete their own datasets."
ON datasets FOR DELETE
USING (auth.uid() = uploaded_by);

-- REVIEWS Policies
CREATE POLICY "Reviews are viewable by everyone."
ON reviews FOR SELECT
USING (true);

CREATE POLICY "Authenticated users can create reviews."
ON reviews FOR INSERT
TO authenticated
WITH CHECK (true);

CREATE POLICY "Users can update their own reviews."
ON reviews FOR UPDATE
USING (auth.uid() = user_id);

CREATE POLICY "Users can delete their own reviews."
ON reviews FOR DELETE
USING (auth.uid() = user_id);

-- DOWNLOADS Policies
CREATE POLICY "Users can view their own downloads."
ON downloads FOR SELECT
USING (auth.uid() = user_id);

CREATE POLICY "Authenticated users can record downloads."
ON downloads FOR INSERT
TO authenticated
WITH CHECK (true);
