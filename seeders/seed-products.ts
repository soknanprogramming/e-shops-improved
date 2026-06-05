import mysql from 'mysql2/promise';
import { faker } from '@faker-js/faker';
import * as dotenv from 'dotenv';
import * as path from 'path';
import chalk from 'chalk';

dotenv.config({ path: path.resolve(__dirname, '../.env') });

// Parse command line arguments
function parseArgs(): { username: string; count: number } {
  const args = process.argv.slice(2);
  let username = '';
  let count = 10;

  for (const arg of args) {
    if (arg.startsWith('--username=')) {
      username = arg.split('=')[1];
    } else if (arg.startsWith('--count=')) {
      count = parseInt(arg.split('=')[1], 10);
    }
  }

  if (!username) {
    console.error('Error: --username is required');
    console.log('Usage: npm run seed -- --username=example [--count=10]');
    process.exit(1);
  }

  if (isNaN(count) || count < 1) {
    console.error('Error: --count must be a positive number');
    process.exit(1);
  }

  return { username, count };
}

// Khmer-inspired product names
const khmerProductPrefixes = [
  'iPhone', 'Samsung', 'Honda', 'Toyota', 'Lexus', 'Sony', 'LG',
  'MacBook', 'Dell', 'HP', 'Canon', 'Nikon', 'Nike', 'Adidas',
  'Gucci', 'Louis Vuitton', 'IKEA', 'Philips', 'Panasonic'
];

const khmerProductSuffixes = [
  'ទំនើប', 'គុណភាពខ្ពស់', 'លក់ពិសេស', 'ម៉ូដថ្មី',
  'ស្អាតណាស់', 'ថ្មី 100%', 'ដើម', 'Premium', 'Limited Edition'
];

const cambodianLocations = [
  'ភ្នំពេញ', 'សៀមរាប', 'បាត់ដំបង', 'កំពង់ចាម',
  'កំពត', 'ក្រចេះ', 'ស្ទឹងត្រែង', 'ព្រះសីហនុ',
  'មណ្ឌលគិរី', 'រតនគិរី', 'កំពង់ធំ', 'ពោធិ៍សាត់'
];

const khmerDescriptions = [
  'ផលិតផលគុណភាពខ្ពស់ អាចទុកចិត្តបាន។',
  'ទំនិញថ្មី 100% មានការធានា។',
  'លក់ពិសេសសម្រាប់រយៈពេលកំណត់។',
  'គុណភាពល្អ តម្លៃសមរម្យ។',
  'ម៉ាកល្បី គុណភាពខ្ពស់។',
  'High quality product, brand new with warranty.',
  'Best price in Cambodia, limited stock available.',
  'Premium quality, imported from international market.',
  'Good condition, barely used, selling due to upgrade.',
  'Original product with full warranty and support.'
];

async function main() {
  const { username, count } = parseArgs();

  console.log(chalk.bold.green('\nStarting seeder...'));
  console.log(chalk.bold('Username:'), chalk.cyan(username));
  console.log(chalk.bold('Products to create:'), chalk.cyan(count + '\n'));

  // Database connection
  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || 'mysql_db',
    user: process.env.DB_USER || 'app_user',
    password: process.env.DB_PASSWORD || 'secret',
    database: process.env.DB_NAME || 'app_db',
    port: parseInt(process.env.DB_PORT || '3306'),
  });

  try {
    // Find user by username (name field)
    const [users] = await connection.query(
      'SELECT id, name FROM `User` WHERE name = ?',
      [username]
    );

    if (!Array.isArray(users) || users.length === 0) {
      console.error(chalk.red(`Error: User "${username}" not found`));
      process.exit(1);
    }

    const user = users[0] as { id: number; name: string };
    console.log(chalk.bold('Found user:'), chalk.green(user.name), chalk.dim(`(ID: ${user.id})`));

    // Get user's profile
    const [profiles] = await connection.query(
      'SELECT id FROM user_profile WHERE user_id = ?',
      [user.id]
    );

    let profileId: number;
    if (Array.isArray(profiles) && profiles.length > 0) {
      profileId = (profiles[0] as { id: number }).id;
    } else {
      // Create profile if it doesn't exist
      const [result] = await connection.query(
        'INSERT INTO user_profile (user_id, phone1) VALUES (?, ?)',
        [user.id, '012345678']
      );
      profileId = (result as any).insertId;
      console.log(chalk.yellow('Created new profile'), chalk.dim(`(ID: ${profileId})`));
    }

    // Get available categories
    const [categories] = await connection.query(
      'SELECT id, name FROM category'
    );

    if (!Array.isArray(categories) || categories.length === 0) {
      console.error(chalk.red('Error: No categories found in database'));
      console.log(chalk.yellow('Please create categories first before seeding products.'));
      process.exit(1);
    }

    console.log(chalk.bold('Found'), chalk.green(categories.length), chalk.bold('categories') + '\n');

    // Get or create default liked and comment records
    let likedId: number;
    const [likedRecords] = await connection.query('SELECT id FROM liked WHERE id = 1');
    if (Array.isArray(likedRecords) && likedRecords.length > 0) {
      likedId = (likedRecords[0] as { id: number }).id;
    } else {
      const [result] = await connection.query(
        'INSERT INTO liked (user_id) VALUES (?)',
        [user.id]
      );
      likedId = (result as any).insertId;
    }

    let commentId: number;
    const [commentRecords] = await connection.query('SELECT id FROM comment WHERE id = 1');
    if (Array.isArray(commentRecords) && commentRecords.length > 0) {
      commentId = (commentRecords[0] as { id: number }).id;
    } else {
      const [result] = await connection.query(
        "INSERT INTO comment (user_id, comment) VALUES (?, ?)",
        [user.id, 'No comments']
      );
      commentId = (result as any).insertId;
    }

    // Create products
    console.log(chalk.bold.blue('Creating products...\n'));

    for (let i = 1; i <= count; i++) {
      // Generate fake product data
      const prefix = faker.helpers.arrayElement(khmerProductPrefixes);
      const suffix = faker.helpers.arrayElement(khmerProductSuffixes);
      const productName = `${prefix} ${faker.commerce.productAdjective()} ${suffix}`;
      
      const price = parseFloat(faker.commerce.price({ min: 10, max: 5000, dec: 2 }));
      const hasDiscount = faker.datatype.boolean({ probability: 0.4 });
      const discount = hasDiscount ? parseFloat(faker.commerce.price({ min: 5, max: 500, dec: 2 })) : null;
      
      const category = faker.helpers.arrayElement(categories as Array<{ id: number; name: string }>);
      const location = faker.helpers.arrayElement(cambodianLocations);
      const description = faker.helpers.arrayElement(khmerDescriptions);

      // Use fake placeholder image (only fake1.jpg exists)
      const mainImage = 'fake/fake1.jpg';
      const images = Array.from({ length: 5 }, () => 
        faker.datatype.boolean({ probability: 0.6 }) 
          ? 'fake/fake1.jpg'
          : null
      );

      // Insert product image
      const [imageResult] = await connection.query(
        `INSERT INTO product_image (main_image, image1, image2, image3, image4, image5) 
         VALUES (?, ?, ?, ?, ?, ?)`,
        [mainImage, images[0], images[1], images[2], images[3], images[4]]
      );
      const imageId = (imageResult as any).insertId;

      // Insert product
      await connection.query(
        `INSERT INTO Product 
         (name, prices, discounts, category_id, owner_id, product_image_id, location, description, showed, profile_id, liked_id, comment_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)`,
        [productName, price, discount, category.id, user.id, imageId, location, description, profileId, likedId, commentId]
      );

      const discountText = discount ? chalk.yellow(` (was $${(price + discount).toFixed(2)})`) : '';
      console.log(chalk.dim(`  [${i}/${count}]`), chalk.bold(productName), chalk.green(`$${price}`) + discountText);
    }

    console.log(chalk.bold.green('\nSuccessfully created ' + count + ' products'));
    console.log(chalk.bold('Owner:'), chalk.cyan(user.name), chalk.dim(`(ID: ${user.id})`));
    console.log(chalk.bold('Total:'), chalk.cyan(count + ' products') + '\n');

  } catch (error) {
    console.error(chalk.red('Error during seeding:'), error);
    process.exit(1);
  } finally {
    await connection.end();
  }
}

main();
