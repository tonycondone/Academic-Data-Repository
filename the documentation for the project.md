Final Year Project Report: Dataset Sharing and Collaboration Platform






Dedication
We dedicate this project to the people who stood by us as we navigated our education, families, and mentors. Their support and guidance helped us stay focused and keep moving forward. We also honor the memories of those we have lost during this time. Their influence continues to give us determination and meaning. This work will inspire future students to expand on the groundwork we have built through this project.














Acknowledgment
We deeply thank Almighty God for giving us the strength, wisdom, and teamwork needed to see this project through to the end.
We owe special thanks to our supervisor and the lecturers in the Department of Data Management and Analytics. They offered us constant support helpful feedback, and academic guidance during this work.
We appreciate the University of Ghana Computing Systems (UGCS) for providing an internship opportunity. This experience improved our practical skills and added significant value to this project.
We are grateful to our classmates, friends, and families for standing by us with your support, encouragement, and trust.










Abstract
The demand to access and manage datasets has significantly grown in the education and tech fields. This project introduces a web-based platform designed to share and work together on datasets. It gives students, educators, and researchers a simple interface to upload, explore, check, and download different datasets. The system allows users to log in, organizes datasets into categories, shows CSV files online, and includes a review and rating system to improve dataset quality and visibility. The platform uses PHP, MySQL, HTML, CSS, and JavaScript, focusing on being easy to use, adaptable, and offering offline CSS support. It helps address the lack of accessible academic data and pushes for teamwork in reviewing datasets in schools and universities.












Chapter 1: Introduction
1.1 Background and Problem Statement
As data-focused studies and tech progress expand, researchers, developers, and students today need trustworthy and easy-to-access datasets. Finding the right datasets for their needs can be tough. Some platforms, like Kaggle and the UCI Machine Learning Repository, solve this issue. However, these platforms sometimes demand advanced technical skills, often overlook regional data needs, and might not include tools like easy interfaces or localized rating systems to assist users.
A missing, easy-to-use central platform to share and review datasets creates challenges for students and researchers in developing areas who rely on accessible data to finish studies or carry out localized work. This project aims to close that gap by creating a web tool. This platform will let admins upload datasets, while users can search by topic, look at or download datasets, and review and rate them.
1.2 Goals
•	Build a user-friendly, flexible platform for students and researchers to access datasets.
•	Add tools to upload, view, and download datasets.
•	Sort datasets into topics to make finding them easier.
•	Allow users to provide ratings and reviews on datasets.
•	Ensure secure logins and assign roles for admins and users.
1.3 Project Scope
This project focuses on creating and setting up an online platform where people can share, browse, and use datasets. Its main parts include:
•	A system for users to sign up and log in.
•	Special admin tools to upload datasets.
•	Features to organize, search, and find datasets.
•	Options to rate datasets and leave reviews.
•	Support for previewing datasets online and downloading them as CSV files.
The platform is not designed for heavy data processing or analysis. Its primary aim is to enable dataset sharing and teamwork among users.
1.4 Importance of the Project
This platform will help students and teachers in school or college environments by making finding sorted and reviewed datasets easier. Adding features like user feedback and a simple design will help users choose data more effectively for learning or research work.













Chapter 2: System Review
2.1 Collecting Needs
Researchers, university mentors, and IT teachers, who often look for datasets to use in lessons or project assessments, provided key feedback. Their suggestions influenced the focus on making data easy to access, organizing it, and ensuring it is user-friendly.
2.2 Intended Users
	Students at universities
	Researchers in academic fields
	Teachers and instructors
	Programmers creating projects based on data
2.3 System Features
	Create a user account and log in
	Allow access based on roles like user or admin
	Let admins upload datasets
	Browse data through categories
	Search for specific items
	Check the dataset details in the browser
	Download datasets
	Rate and review datasets
2.4 Non-Functional Requirements
	Design a web platform that adjusts to both desktop and mobile
	Ensure easy and quick navigation
	Use secure login methods
	Add offline support using CSS
	Keep the backend lightweight with fewer dependencies
2.5 Related Works and Literature Review
Several platforms have emerged to facilitate dataset sharing and collaboration in data science. Notable among them are Kaggle, UCI Machine Learning Repository, and Google Dataset Search. Each offers distinct features and serves different user groups but also presents limitations. This section reviews those platforms and highlights how the proposed system builds upon their strengths while addressing their limitations.
Kaggle
Kaggle is a popular online platform for data science competitions and data sharing. It features a large repository of public datasets, collaborative tools like Kaggle Notebooks, and community-driven competitions. While highly effective for skilled data scientists, Kaggle assumes users have programming expertise and lacks native features like dataset rating or local data categorization.
Our platform addresses these by introducing a simple interface with category-based browsing, offline access, and a built-in rating/review system making it more student- and research-friendly.

UCI Machine Learning Repository
UCI provides a well-known archive of machine learning datasets, often used in academic settings. It offers downloadable datasets with documentation but lacks interactivity, dataset previews, or community engagement features.
Our system enhances this by supporting online preview (for CSVs), dataset upload moderation, and user-generated reviews, creating a more dynamic and collaborative environment.

Google Dataset Search
Google Dataset Search is a meta-search tool for finding datasets across websites. It indexes metadata using schema.org tags and offers filters, but does not host data or allow uploads or user reviews.
Our project acts both as a repository and interactive platform, offering localized content management, community rating, and simple upload options, effectively complementing Google’s discovery role with practical tools for collaboration.

















Chapter 3: System Design
3.1 System Architecture
	The platform uses a client-server setup where:
	The frontend is built using HTML, CSS, and JavaScript
	PHP powers the backend
	MySQL is used to store data
PHP scripts manage user requests from the browser by interacting with the MySQL database. These scripts either fetch data or generate HTML views based on the communication.
3.2 Entity Relationship Diagram (ERD)
Entities:
	Users: Includes user_id, name, email, and role (admin or user).
	Datasets: Contains dataset_id, title, filename, category, description, and upload date.
	Reviews: Holds review_id, user_id, dataset_id, rating, comment, and timestamp.
Relationships:
	Users can have multiple Datasets.
	Users can leave various Reviews.
	Datasets can receive various Reviews.
3.3 Database Schema (Simplified)
Users Table
	id (Primary Key)
	name
	email
	password
	role
Datasets Table
	id (Primary Key)
	title
	filename
	category
	description
	upload_date
Reviews Table
	id (Primary Key)
	user_id (Foreign Key)
	dataset_id (Foreign Key)
	rating (INT)
	comment (TEXT)
	timestamp
3.4 Interface Design
The interface is built to be simple and effective.
	A top navigation bar includes a search field, the user icon showing profile and logout options, and the site name or logo.
	A sidebar allows filtering by categories and a button to reset filters.
	The central section has dataset cards that let users view, download, rate, or leave a review.


3.5 File Handling
	People often use CSV files to view datasets due to their simplicity.
	The system converts Excel files (.xls, .xlsx) to CSV before uploading them.
	The platform keeps uploaded files in a specific folder and connects them to the database.
3.6 Security Implementation
	SQL Injection Prevention: All database queries use parameterized statements via PHP's PDO (PHP Data Objects).
	Password Security: Passwords are hashed using bcrypt (via `password_hash()` with `PASSWORD_BCRYPT`).
	Session Management:
•	Sessions use secure, HTTP-only cookies.
•	Session IDs are regenerated after login to prevent fixation.
•	Session timeouts are set to 30 minutes of inactivity.
•	Cross-Site Request Forgery (CSRF) Protection: Critical forms (e.g., dataset upload, review submission) include CSRF tokens.
•	Access Control: Role-based checks are performed before granting access to administrative functions.
3.7 Use Case Diagram
 
