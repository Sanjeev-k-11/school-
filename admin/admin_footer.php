<?php
 
 
$currentYear = date('Y');
 
$schoolName = "Basic Public School";
$schoolPhone = "8877780197";
$schoolEmail = "info@yourschool.com";
$developerName = "Sanjeev Kumar";
$developerWebsite = "https://github.com/Sanjeev-k-11";
$developerPhone = "9534757076";
$developerEmail = "dev606733@gmial.com";

$socialLinks = [
    'linkedin' => 'https://www.linkedin.com/in/sanjeevkumaryadav/',
    'instagram' => 'https://www.instagram.com/sanjeev_k_11/',
    'facebook' => '#',
    'twitter' => '#'
];
?>

    <footer class="app-footer">
        <div class="footer-container">
            <div class="footer-columns">
                <div class="footer-col">
                    <h4>Contact Us</h4>
                    <ul>
                        <li>
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-sm">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.181.42l-2.36 3.54a3.752 3.752 0 01-3.75-3.75l3.54-2.36c.365-.279.53-.74.42-1.18L13.5 7.101a2.25 2.25 0 00-1.091-.852H10.5a2.25 2.25 0 00-2.25 2.25v2.25z" />
                              </svg>
                            <span><?php echo htmlspecialchars($schoolPhone); ?></span>
                        </li>
                         <li>
                             <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="icon-sm">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                              </svg>
                             <span><?php echo htmlspecialchars($schoolEmail); ?></span>
                         </li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="./admin_dashboard.php">Dashboard</a></li>
                        <li><a href="./student_monthly_fees_list.php">Fee List</a></li>
                        <li><a href="./create_student.php">Admissions</a></li>
                        <li><a href="./manage_staff.php">Staff</a></li>
                        <li><a href="./create_event.php">Events</a></li>
                         <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Student</h4>
                    <ul>
                        <li><a href="./allstudentList.php">Manage Student</a></li>
                        <li><a href="./add_bulk_monthly_fee.php">Bulk Fee Student</a></li>
                        <li><a href="./manage_students.php">Fee Due</a></li>
                         <li><a href="./add_expense.php">Add Expense</a></li>
                         <li><a href="./manage_income.php">All Income</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-branding">
                    <span class="school-name"><?php echo htmlspecialchars($schoolName); ?></span>
                     <span class="school-tagline">Empowering Future Generations</span>
                </div>

                <div class="footer-legal">
                    <a href="#">Privacy Policy</a> | <a href="#">Terms & Conditions</a>
                     <p class="copyright">© <?php echo $currentYear; ?> <?php echo htmlspecialchars($schoolName); ?>. All rights reserved.</p>
                </div>

                 <div class="footer-social">
                      <p>Follow us:</p>
                      <div class="social-icons">
                           <?php if ($socialLinks['linkedin'] != '#'): ?><a href="<?php echo htmlspecialchars($socialLinks['linkedin']); ?>" target="_blank" aria-label="LinkedIn">In</a><?php endif; ?>
                           <?php if ($socialLinks['instagram'] != '#'): ?><a href="<?php echo htmlspecialchars($socialLinks['instagram']); ?>" target="_blank" aria-label="Instagram">Ig</a><?php endif; ?>
                           <?php if ($socialLinks['facebook'] != '#'): ?><a href="<?php echo htmlspecialchars($socialLinks['facebook']); ?>" target="_blank" aria-label="Facebook">Fb</a><?php endif; ?>
                           <?php if ($socialLinks['twitter'] != '#'): ?><a href="<?php echo htmlspecialchars($socialLinks['twitter']); ?>" target="_blank" aria-label="Twitter">X</a><?php endif; ?>
                      </div>
                 </div>

                <?php if (!empty($developerName)): ?>
                 <div class="footer-developer">
                      <?php echo htmlspecialchars($developerName); ?>
                      <?php if (!empty($developerWebsite)): ?> | <a href="<?php echo htmlspecialchars($developerWebsite); ?>" target="_blank">Website</a><?php endif; ?>
                      <?php if (!empty($developerPhone) && $developerPhone != 'YOUR_DEV_PHONE'): ?> | <a href="tel:<?php echo htmlspecialchars($developerPhone); ?>">Call</a><?php endif; ?>
                      <?php if (!empty($developerEmail) && $developerEmail != 'YOUR_DEV_EMAIL'): ?> | <a href="mailto:<?php echo htmlspecialchars($developerEmail); ?>">Email</a><?php endif; ?>
                 </div>
             <?php endif; ?>

            </div>
        </div>
    </footer>

    <style>
        .app-footer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .app-footer li {
            margin-bottom: 0.5rem;
             display: flex;
             align-items: center;
        }
        .app-footer a {
            text-decoration: none;
            color: inherit;
            transition: color 0.2s ease-in-out;
        }
        .app-footer a:hover {
            color: #ef4444;
            text-decoration: underline;
        }

        .app-footer {
            background-color: #1f2937;
            color: #d1d5db;
            padding: 3rem 0;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        .footer-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
             padding-bottom: 2rem;
             border-bottom: 1px solid #374151;
        }

        .footer-col h4 {
            color: #f3f4f6;
            font-size: 1rem;
            margin-bottom: 1rem;
             font-weight: 600;
        }

        .footer-col ul li a {
            color: #9ca3af;
            display: inline-block;
            margin-left: 0.5rem;
        }
         .footer-col ul li svg {
              width: 1rem;
              height: 1rem;
              color: #9ca3af;
              flex-shrink: 0;
         }

        .icon-sm {
             width: 1rem;
             height: 1rem;
             vertical-align: middle;
             margin-right: 0.5rem;
             color: #9ca3af;
        }


        .footer-bottom {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
             padding-top: 2rem;
        }

        .footer-branding {
             flex-basis: 250px;
             flex-grow: 1;
             min-width: 150px;
             color: #f3f4f6;
             font-size: 1.25rem;
             font-weight: 700;
        }
         .footer-logo {
              height: 40px;
              margin-right: 10px;
              vertical-align: middle;
         }
         .school-name {
             display: block;
             font-size: 1.25rem;
             font-weight: 700;
             margin-bottom: 0.2rem;
         }
          .school-tagline {
              display: block;
              font-size: 0.8rem;
              font-weight: 400;
              color: #9ca3af;
          }


        .footer-legal {
             flex-grow: 1;
             text-align: center;
             min-width: 200px;
        }
        .footer-legal a {
             color: #9ca3af;
             margin: 0 0.5rem;
        }
         .footer-legal a:hover {
             color: #ef4444;
         }
        .footer-legal .copyright {
             margin-top: 0.5rem;
             font-size: 0.85rem;
             color: #6b7280;
        }


        .footer-social {
             flex-basis: 200px;
             flex-grow: 1;
             text-align: right;
             min-width: 150px;
        }
        .footer-social p {
            margin-bottom: 0.5rem;
             color: #f3f4f6;
             font-weight: 600;
        }
        .social-icons a {
            display: inline-block;
            margin-left: 0.8rem;
            font-size: 1.2rem;
            color: #9ca3af;
        }
         .social-icons a:hover {
             color: #ef4444;
         }


        .footer-developer {
            width: 100%;
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #6b7280;
             border-top: 1px dashed #374151;
             padding-top: 1.5rem;
        }
        .footer-developer a {
             color: #6b7280;
             text-decoration: underline;
        }
         .footer-developer a:hover {
              color: #d1d5db;
         }


        @media (max-width: 768px) {
            .footer-columns {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
             .footer-bottom {
                 flex-direction: column;
                 text-align: center;
             }
             .footer-branding,
             .footer-legal,
             .footer-social {
                 flex-basis: auto;
                 width: 100%;
                 text-align: center;
             }
             .footer-social .social-icons a {
                 margin: 0 0.5rem;
             }
        }

    </style>

</body>
</html>