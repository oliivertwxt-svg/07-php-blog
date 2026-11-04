<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/app/bootstrap.php';

$adminId = (int) db()->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
if ($adminId === 0) {
    fwrite(STDERR, "No admin account exists. Create one first with create-admin.php.\n");
    exit(1);
}

$seedPosts = [
    [
        'category' => 'Tutorials',
        'title' => 'A Practical Guide to Planning Your First Website',
        'excerpt' => 'Turn a rough idea into a clear, achievable website plan before you start coding.',
        'body' => "Start with the problem your website should solve and the people it should help. Write down the three most important things a visitor needs to do.\n\nSketch a few pages on paper, then decide what information belongs on each one. A small, focused first release is easier to build, test, and improve than a long feature wishlist.",
        'status' => 'published',
    ],
    [
        'category' => 'Tutorials',
        'title' => 'Make a Website Easier to Read',
        'excerpt' => 'A few typography and spacing choices can make long pages feel much more welcoming.',
        'body' => "Use a comfortable line length, generous line spacing, and clear headings to help readers find their place. Keep paragraphs focused on one idea rather than making every section a wall of text.\n\nCheck the page at a narrow screen width as well as on a desktop. Good reading rhythm should not depend on a large monitor.",
        'status' => 'published',
    ],
    [
        'category' => 'Tutorials',
        'title' => 'A Friendly Introduction to Semantic HTML',
        'excerpt' => 'Choose elements for what they mean, not just for how they look.',
        'body' => "Semantic HTML describes the purpose of the content. A navigation region belongs in a nav element, the main page content belongs in main, and a standalone story can be marked up as an article.\n\nMeaningful structure helps assistive technology and makes pages easier for other developers to understand. Start with a useful document outline before adding decorative details.",
        'status' => 'published',
    ],
    [
        'category' => 'Tutorials',
        'title' => 'A Simple Checklist for Testing Forms',
        'excerpt' => 'Test the expected path, the awkward edge cases, and the error messages.',
        'body' => "Submit a form with valid information, missing required values, and data that is longer than expected. Confirm that errors explain how to recover and that entered values are preserved when appropriate.\n\nAlso test keyboard navigation and verify that labels are connected to their fields. Small checks like these catch issues before visitors do.",
        'status' => 'draft',
    ],
    [
        'category' => 'Behind the Scenes',
        'title' => 'How We Turned a Blog Idea into a Small CMS',
        'excerpt' => 'A look at the design decisions behind a lightweight publishing workflow.',
        'body' => "We began with the essentials: a place to write, a way to organize posts, and a public reading experience. Keeping the first version small let us focus on the workflow instead of building features nobody had asked for.\n\nThe result is a manageable foundation that can grow as real publishing needs become clearer.",
        'status' => 'published',
    ],
    [
        'category' => 'Behind the Scenes',
        'title' => 'What We Learned from Improving the Admin Dashboard',
        'excerpt' => 'The most useful dashboard is the one that makes the next task obvious.',
        'body' => "We brought the most frequently needed information together: publishing totals, recent updates, and category activity. That means fewer clicks just to understand what is happening.\n\nWe also kept the data summaries simple and made the recent posts list actionable, so a title leads directly to its edit screen.",
        'status' => 'published',
    ],
    [
        'category' => 'Behind the Scenes',
        'title' => 'Choosing a Calm Visual Style for Long Reads',
        'excerpt' => 'Readable type, quiet colors, and responsive layouts give stories room to breathe.',
        'body' => "A content-focused site does not need to compete with its own articles. We chose a restrained palette, clear contrast, and a narrow reading column so attention stays on the writing.\n\nCards and navigation still provide structure, while responsive spacing keeps the same calm feel on smaller screens.",
        'status' => 'published',
    ],
    [
        'category' => 'Community',
        'title' => 'Five Questions to Ask Before Starting a Side Project',
        'excerpt' => 'A little reflection can keep a weekend project fun and finishable.',
        'body' => "Who is this for? What is the smallest useful version? Which part do you most want to learn? How will you know the first version is done? What can wait until later?\n\nAnswering these questions turns a broad idea into a project you can actually enjoy completing.",
        'status' => 'published',
    ],
    [
        'category' => 'Community',
        'title' => 'Share Your Work and Invite Useful Feedback',
        'excerpt' => 'Specific questions help friends and collaborators give feedback you can use.',
        'body' => "When you share a project, explain what you are trying to accomplish and where you are uncertain. Asking someone to try one task often gives you more useful feedback than asking whether they like the whole site.\n\nListen for repeated points, decide what fits your goals, and remember that feedback is information rather than a verdict.",
        'status' => 'published',
    ],
    [
        'category' => 'Community',
        'title' => 'A Small Routine for Keeping Projects Moving',
        'excerpt' => 'Short, regular sessions can be easier to sustain than occasional marathons.',
        'body' => "At the end of each work session, leave a note about the next small action. When you return, you can get started without reconstructing the entire plan.\n\nCelebrate finished steps, keep the task list realistic, and make room for rest. Consistency is built from routines that fit real life.",
        'status' => 'draft',
    ],
    [
        'category' => 'News',
        'title' => 'A Fresh Home for Stories and Tutorials',
        'excerpt' => 'Welcome to our new space for practical notes, thoughtful experiments, and updates.',
        'body' => "We are opening this blog as a place to collect ideas worth returning to. Expect practical tutorials, notes from projects in progress, and occasional updates from the community.\n\nThanks for reading the first post. We hope you find something useful here and come back for what is next.",
        'status' => 'published',
    ],
    [
        'category' => 'News',
        'title' => 'What Is Coming to the Blog This Season',
        'excerpt' => 'A preview of the topics and stories we are excited to share next.',
        'body' => "Over the coming months, we plan to share approachable web development guides, lessons learned from building small tools, and stories from people who make things together.\n\nThe plan is flexible by design. Your questions and suggestions will help shape what we write next.",
        'status' => 'published',
    ],
];

$pdo = db();
$pdo->beginTransaction();

try {
    $categoryQuery = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    $categoryInsert = $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)');
    $postQuery = $pdo->prepare('SELECT id FROM posts WHERE slug = ?');
    $postInsert = $pdo->prepare(
        "INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, published_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, IF(? = 'published', NOW(), NULL))"
    );
    $categories = [];
    $created = 0;
    $skipped = 0;

    foreach ($seedPosts as $seedPost) {
        $categoryName = $seedPost['category'];
        $categorySlug = slugify($categoryName);
        if (!isset($categories[$categorySlug])) {
            $categoryQuery->execute([$categorySlug]);
            $categoryId = $categoryQuery->fetchColumn();
            if ($categoryId === false) {
                $categoryInsert->execute([$categoryName, $categorySlug]);
                $categoryId = $pdo->lastInsertId();
            }
            $categories[$categorySlug] = (int) $categoryId;
        }

        $slug = slugify($seedPost['title']);
        $postQuery->execute([$slug]);
        if ($postQuery->fetchColumn() !== false) {
            $skipped++;
            continue;
        }

        $postInsert->execute([
            $categories[$categorySlug],
            $adminId,
            $seedPost['title'],
            $slug,
            $seedPost['excerpt'],
            $seedPost['body'],
            $seedPost['status'],
            $seedPost['status'],
        ]);
        $created++;
    }

    $pdo->commit();
    fwrite(STDOUT, "Seed complete: {$created} post(s) created, {$skipped} existing post(s) skipped.\n");
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Seed failed: {$exception->getMessage()}\n");
    exit(1);
}
