<?php
namespace PhpMimeMailParser;

use PhpMimeMailParser\Parser;
use PhpMimeMailParser\Attachment;
use PhpMimeMailParser\Exception;

/**
 * Test Parser of php-mime-mail-parser
 *
 * Fully Tested Mailparse Extension Wrapper for PHP 5.4+
 *
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{

    public function provideData()
    {

        $data = array(
            /*
            array(
                // Mail ID
                'm0001',
                // Subject Expected
                'Mail avec fichier attaché de 1ko',
                // From Expected
                'Name <name@company.com>',
                // To Expected
                'name@company2.com',
                // Text Expected (MATCH = exact match, COUNT = Count the number of substring occurrences )
                array('MATCH',"\n"),
                // Html Expected (MATCH = exact match, COUNT = Count the number of substring occurrences )
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                // Array of attachments (FileName, File Size, String inside the fil, 
                //      Count of this string, ContentType, MD5 of Serialize Headers)
                array(array('attach01',2,'a',1,'image/gif','attachment', '4c1d5793')),
                // Count of Embedded Attachments
                0)
            */
            array(
                'm0001',
                'Mail avec fichier attaché de 1ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach01',
                        2,
                        'a',
                        1,
                        'application/octet-stream',
                        'attachment',
                        '04c1d5793efa97c956d011a8b3309f05'
                    )
                ),
                0),
                array(
                'm0002',
                'Mail avec fichier attaché de 3ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach02',
                        2229,
                        'Lorem ipsum',
                        8,
                        'application/octet-stream',
                        'attachment',
                        '18f541cc6bf49209d2bf327ecb887355'
                    )
                ),
                0),
                array(
                'm0003',
                'Mail de 14 Ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach03',
                        13369,
                        'dolor sit amet',
                        48,
                        'application/octet-stream',
                        'attachment',
                        '8734417734fabfa783df6fed0ccf7a4a'
                    )
                ),
                0),
                array(
                'm0004',
                'Mail de 800ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach04',
                        817938,
                        'Phasellus scelerisque',
                        242,
                        'application/octet-stream',
                        'attachment',
                        'c0b5348ef825bf62ba2d07d70d4b9560'
                    )
                ),
                0),
                array(
                'm0005',
                'Mail de 1500 Ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach05',
                        1635877,
                        'Aenean ultrices',
                        484,
                        'application/octet-stream',
                        'attachment',
                        '1ced323befc39ebbc147e7588d11ab08'
                    )
                ),
                0),
                array(
                'm0006',
                'Mail de 3 196 Ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach06',
                        3271754,
                        'lectus ac leo ullamcorper',
                        968,
                        'application/octet-stream',
                        'attachment',
                        '5dc6470ab63e86e8f68d88afb11556fe'
                    )
                ),
                0),
                array(
                'm0007',
                'Mail avec fichier attaché de 3ko',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'attach02',
                        2229,
                        'facilisis',
                        4,
                        'application/octet-stream',
                        'attachment',
                        '0e6d510323b009da939070faf72e521c'
                    )
                ),
                0),
                array(
                'm0008',
                'Testing MIME E-mail composing with cid',
                'Name <name@company.com>',
                'Name <name@company2.com>',
                array('COUNT',1,'Please use an HTML capable mail program to read'),
                array('COUNT',1,'<center><h1>Testing MIME E-mail composing with cid</h1></center>'),
                array(
                    array(
                        'logo.jpg',
                        2695,
                        '',
                        0,
                        'image/gif',
                        'inline',
                        '102aa12e16635bf2b0b39ef6a91aa95c'
                    ),
                    array(
                        'background.jpg',
                        18255,
                        '',
                        0,
                        'image/gif',
                        'inline',
                        '798f976a5834019d3f2dd087be5d5796'
                    ),
                    array(
                        'attachment.txt',
                        2229,
                        'Sed pulvinar',
                        4,
                        'text/plain',
                        'attachment',
                        '71fff85a7960460bdd3c4b8f1ee9279b'
                    )
                ),
                2),
                array(
                'm0009',
                'Ogone NIEUWE order Maurits PAYID: 951597484 / orderID: 456123 / status: 5',
                '"Ogone" <noreply@ogone.com>',
                'info@testsite.com',
                array('COUNT',1,'951597484'),
                array('MATCH',''),
                array(),
                0),
                array(
                'm0010',
                'Mail de 800ko without filename',
                'Name <name@company.com>',
                'name@company2.com',
                array('MATCH',"\n"),
                array('COUNT',1,'<div dir="ltr"><br></div>'),
                array(
                    array(
                        'noname1',
                        817938,
                        'Suspendisse',
                        726,
                        'application/octet-stream',
                        'attachment',
                        '8da4b0177297b1d7f061e44d64cc766f'
                    )
                ),
                0),
                array(
                'm0011',
                'Hello World !',
                'Name <name@company.com>',
                'Name <name@company.com>',
                array('COUNT',1,'This is a text body'),
                array('MATCH',''),
                array(
                    array(
                        'file.txt',
                        29,
                        'This is a file',
                        1,
                        'text/plain',
                        'attachment',
                        '839d0486dd1b91e520d456bb17c33148'
                    )
                ),
                0),
                array(
                'm0012',
                'Hello World !',
                'Name <name@company.com>',
                'Name <name@company.com>',
                array('COUNT',1,'This is a text body'),
                array('MATCH',''),
                array(
                    array(
                        'file.txt',
                        29,
                        'This is a file',
                        1,
                        'text/plain',
                        'attachment',
                        '839d0486dd1b91e520d456bb17c33148'
                    )
                ),
                0),
                array(
                'm0013',
                '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829. pdf',
                'NAME Firstname <firstname.name@groupe-company.com>',
                '"paul.dupont@company.com" <paul.dupont@company.com>',
                array('COUNT',1,'Superviseur de voitures'),
                array('MATCH',''),
                array(
                    array(
                        '50032266 CAR 11_MNPA00A01_9PTX_H00 ATT N° 1467829.pdf',
                        10,
                        '',
                        0,
                        'application/pdf',
                        'attachment',
                        'ffe2cb0f5df4e2cfffd3931b6566f3cb'
                    )
                ),
                0),
                array(
                'm0014',
                'Test message from Netscape Communicator 4.7',
                'Doug Sauder <dwsauder@example.com>',
                'Joe Blow <blow@example.com>',
                array('COUNT',1,'Die Hasen und die'),
                array('MATCH',''),
                array(
                    array(
                        'HasenundFrösche.txt',
                        747,
                        'noch',
                        2,
                        'text/plain',
                        'inline',
                        '865238356eec20b67ce8c33c68d8a95a'
                    )
                ),
                0),
                array(
                'm0015',
                'Up to $30 Off Multivitamins!',
                '"Vitamart.ca" <service@vitamart.ca>',
                'me@somewhere.com',
                array('COUNT',1,'Hi,'),
                array('COUNT',1,'<strong>*How The Sale Works</strong>'),
                array(
                    array(
                        'noname1',
                        2616,
                        '$150+ of Multivitamins',
                        1,
                        'text/plain',
                        'inline',
                        '87caaaf9bf1d7ebc2769254710c38a0d'
                    ),
                    array(
                        'noname2',
                        17341,
                        'div',
                        82,
                        'text/html',
                        'inline',
                        'b70ff760112a71009d8295c34fd67d9b'
                    )
                ),
                0),
                array(
                'm0016',
                'Test message with multiple From headers',
                'Doug Sauder <dwsauder@example.com>',
                'Joe Blow <blow@example.com>',
                array('COUNT',1,'Die Hasen und die'),
                array('MATCH',''),
                array(
                    array(
                        'HasenundFrösche.txt',
                        747,
                        'noch',
                        2,
                        'text/plain',
                        'inline',
                        '865238356eec20b67ce8c33c68d8a95a'
                    )
                ),
                0),
                array(
                'm0018',
                '[Korea] Name',
                '<name@company.com>',
                '"name@company2.com" <name@company2.com>',
                array('COUNT',1,'My traveling companions!'),
                array('MATCH',''),
                array(
                    array(
                        '사진.JPG',
                        174,
                        '',
                        0,
                        'image/jpeg',
                        'attachment',
                        '567f29989506f21cea8ac992d81ce4c1'
                    ),
                    array(
                        'ATT00001.txt',
                        25,
                        'iPhone',
                        1,
                        'text/plain',
                        'attachment',
                        '095f96b9d5a25d051ad425356745334f'
                    )
                ),
                0),
                array(
                'm0019',
                'Re: Maya Ethnobotanicals - Emails',
                'sendeär <sender@test.com>',
                '"test" <test@asdasd.com>',
                array('COUNT',1,'captured'),
                array('MATCH',''),
                array(),
                0),
                array(
                'm0020',
                '1',
                'Finntack Newsletter <newsletter@finntack.com>',
                'Clement Wong <clement.wong@finntack.com>',
                array('MATCH',"1\r\n\r\n"),
                array('COUNT',1,'<html>'),
                array(
                    array(
                        'noname1',
                        1432,
                        '',
                        0,
                        'text/calendar',
                        'attachment',
                        'bf7bfb9b8dd11ff0c830b2388560d434'
                    )
                ),
                0),
                array(
                'm0021',
                'problem occurs when divided into an array, and the last e of the array! Пут ін хуйло!!!!!!',
                'mail@exemple.com',
                'mail@exemple.com, mail2@exemple3.com, mail3@exemple2.com',
                array('COUNT',1,'mini plain body'),
                array('MATCH',''),
                array(),
                0),
                array(
                'm0022',
                '[PRJ-OTH] asdf  árvíztűrő tükörfúrógép',
                'sendeär <sender@test.com>',
                '"test" <test@asdasd.com>',
                array('COUNT',1,'captured'),
                array('MATCH',''),
                array(),
                0),
                array(
                'm0023',
                'If you can read this you understand the example.',
                'Keith Moore <moore@cs.utk.edu>',
                'Keld Jørn Simonsen <keld@dkuug.dk>',
                //CC = André Pirard <PIRARD@vm1.ulg.ac.be>
                array('COUNT',1,'captured'),
                array('MATCH',''),
                array(),
                0),
        );
        return $data;
    }

    /**
     * @dataProvider provideData
     */
    public function testFromPath(
        $mid,
        $subjectExpected,
        $fromExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = __DIR__.'/mails/attach_'.$mid.'/';

        //Load From Path
        $Parser = new Parser();
        $Parser->setPath($file);

        //Test Header : subject
        $this->assertEquals($subjectExpected, $Parser->getHeader('subject'));
        $this->assertArrayHasKey('subject', $Parser->getHeaders());

        //Test Header : from
        $this->assertEquals($fromExpected, $Parser->getHeader('from'));
        $this->assertArrayHasKey('from', $Parser->getHeaders());

        //Test Header : to
        $this->assertEquals($toExpected, $Parser->getHeader('to'));
        $this->assertArrayHasKey('to', $Parser->getHeaders());

        //Test Invalid Header
        $this->assertFalse($Parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $Parser->getHeaders());

        //Test  Body : text
        if ($textExpected[0] == 'COUNT') {
            $this->assertEquals($textExpected[1], substr_count($Parser->getMessageBody('text'), $textExpected[2]));
        } elseif ($textExpected[0] == 'MATCH') {
            $this->assertEquals($textExpected[1], $Parser->getMessageBody('text'));
        }

        //Test Body : html
        if ($htmlExpected[0] == 'COUNT') {
            $this->assertEquals($htmlExpected[1], substr_count($Parser->getMessageBody('html'), $htmlExpected[2]));
        } elseif ($htmlExpected[0] == 'MATCH') {
            $this->assertEquals($htmlExpected[1], $Parser->getMessageBody('html'));
        }

        //Test Nb Attachments
        $attachments = $Parser->getAttachments();
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        if (count($attachmentsExpected) > 0) {
            //Save attachments
            $Parser->saveAttachments($attach_dir);

            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Exist Attachment
                $this->assertTrue(file_exists($attach_dir.$attachmentExpected[0]));

                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachmentExpected[1],
                    filesize($attach_dir.$attachments[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachmentExpected[2] != '' && $attachmentExpected[3] >0) {
                    $fileContent = file_get_contents(
                        $attach_dir.$attachments[$iterAttachments]->getFilename(),
                        FILE_USE_INCLUDE_PATH
                    );
                    $this->assertEquals($attachmentExpected[3], substr_count($fileContent, $attachmentExpected[2]));
                    $this->assertEquals(
                        $attachmentExpected[3],
                        substr_count($attachments[$iterAttachments]->getContent(), $attachmentExpected[2])
                    );
                }
                
                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                //Remove Attachment
                unlink($attach_dir.$attachments[$iterAttachments]->getFilename());

                $iterAttachments++;

            }
            //Remove Attachment Directory
            rmdir($attach_dir);
        } else {
             $this->assertFalse($Parser->saveAttachments($attach_dir));
        }

        //Test embedded Attachments
        $htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
        $this->assertEquals($countEmbeddedExpected, substr_count($htmlEmbedded, "data:"));
    }

    /**
     * @dataProvider provideData
     */
    public function testFromText(
        $mid,
        $subjectExpected,
        $fromExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = __DIR__.'/mails/attach_'.$mid.'/';

        //Load From Text
        $Parser = new Parser();
        $Parser->setText(file_get_contents($file));

        //Test Header : subject
        $this->assertEquals($subjectExpected, $Parser->getHeader('subject'));
        $this->assertArrayHasKey('subject', $Parser->getHeaders());

        //Test Header : from
        $this->assertEquals($fromExpected, $Parser->getHeader('from'));
        $this->assertArrayHasKey('from', $Parser->getHeaders());

        //Test Header : to
        $this->assertEquals($toExpected, $Parser->getHeader('to'));
        $this->assertArrayHasKey('to', $Parser->getHeaders());

        //Test Invalid Header
        $this->assertFalse($Parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $Parser->getHeaders());

        //Test  Body : text
        if ($textExpected[0] == 'COUNT') {
            $this->assertEquals($textExpected[1], substr_count($Parser->getMessageBody('text'), $textExpected[2]));
        } elseif ($textExpected[0] == 'MATCH') {
            $this->assertEquals($textExpected[1], $Parser->getMessageBody('text'));
        }

        //Test Body : html
        if ($htmlExpected[0] == 'COUNT') {
            $this->assertEquals($htmlExpected[1], substr_count($Parser->getMessageBody('html'), $htmlExpected[2]));
        } elseif ($htmlExpected[0] == 'MATCH') {
            $this->assertEquals($htmlExpected[1], $Parser->getMessageBody('html'));
        }

        //Test Nb Attachments
        $attachments = $Parser->getAttachments();
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        if (count($attachmentsExpected) > 0) {
            //Save attachments
            $Parser->saveAttachments($attach_dir);

            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Exist Attachment
                $this->assertTrue(file_exists($attach_dir.$attachmentExpected[0]));

                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachmentExpected[1],
                    filesize($attach_dir.$attachments[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachmentExpected[2] != '' && $attachmentExpected[3] >0) {
                    $fileContent = file_get_contents(
                        $attach_dir.$attachments[$iterAttachments]->getFilename(),
                        FILE_USE_INCLUDE_PATH
                    );
                    $this->assertEquals($attachmentExpected[3], substr_count($fileContent, $attachmentExpected[2]));
                    $this->assertEquals(
                        $attachmentExpected[3],
                        substr_count($attachments[$iterAttachments]->getContent(), $attachmentExpected[2])
                    );
                }
                
                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                //Remove Attachment
                unlink($attach_dir.$attachments[$iterAttachments]->getFilename());

                $iterAttachments++;

            }
            //Remove Attachment Directory
            rmdir($attach_dir);
        } else {
             $this->assertFalse($Parser->saveAttachments($attach_dir));
        }

        //Test embedded Attachments
        $htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
        $this->assertEquals($countEmbeddedExpected, substr_count($htmlEmbedded, "data:"));
    }


    /**
     * @dataProvider provideData
     */
    public function testFromStream(
        $mid,
        $subjectExpected,
        $fromExpected,
        $toExpected,
        $textExpected,
        $htmlExpected,
        $attachmentsExpected,
        $countEmbeddedExpected
    ) {
        //Init
        $file = __DIR__.'/mails/'.$mid;
        $attach_dir = __DIR__.'/mails/attach_'.$mid.'/';

        //Load From Path
        $Parser = new Parser();
        $Parser->setStream(fopen($file, 'r'));

        //Test Header : subject
        $this->assertEquals($subjectExpected, $Parser->getHeader('subject'));
        $this->assertArrayHasKey('subject', $Parser->getHeaders());

        //Test Header : from
        $this->assertEquals($fromExpected, $Parser->getHeader('from'));
        $this->assertArrayHasKey('from', $Parser->getHeaders());

        //Test Header : to
        $this->assertEquals($toExpected, $Parser->getHeader('to'));
        $this->assertArrayHasKey('to', $Parser->getHeaders());

        //Test Invalid Header
        $this->assertFalse($Parser->getHeader('azerty'));
        $this->assertArrayNotHasKey('azerty', $Parser->getHeaders());

        //Test  Body : text
        if ($textExpected[0] == 'COUNT') {
            $this->assertEquals($textExpected[1], substr_count($Parser->getMessageBody('text'), $textExpected[2]));
        } elseif ($textExpected[0] == 'MATCH') {
            $this->assertEquals($textExpected[1], $Parser->getMessageBody('text'));
        }

        //Test Body : html
        if ($htmlExpected[0] == 'COUNT') {
            $this->assertEquals($htmlExpected[1], substr_count($Parser->getMessageBody('html'), $htmlExpected[2]));
        } elseif ($htmlExpected[0] == 'MATCH') {
            $this->assertEquals($htmlExpected[1], $Parser->getMessageBody('html'));
        }

        //Test Nb Attachments
        $attachments = $Parser->getAttachments();
        $this->assertEquals(count($attachmentsExpected), count($attachments));
        $iterAttachments = 0;

        //Test Attachments
        if (count($attachmentsExpected) > 0) {
            //Save attachments
            $Parser->saveAttachments($attach_dir);

            foreach ($attachmentsExpected as $attachmentExpected) {
                //Test Exist Attachment
                $this->assertTrue(file_exists($attach_dir.$attachmentExpected[0]));

                //Test Filename Attachment
                $this->assertEquals($attachmentExpected[0], $attachments[$iterAttachments]->getFilename());

                //Test Size Attachment
                $this->assertEquals(
                    $attachmentExpected[1],
                    filesize($attach_dir.$attachments[$iterAttachments]->getFilename())
                );

                //Test Inside Attachment
                if ($attachmentExpected[2] != '' && $attachmentExpected[3] >0) {
                    $fileContent = file_get_contents(
                        $attach_dir.$attachments[$iterAttachments]->getFilename(),
                        FILE_USE_INCLUDE_PATH
                    );
                    $this->assertEquals($attachmentExpected[3], substr_count($fileContent, $attachmentExpected[2]));
                    $this->assertEquals(
                        $attachmentExpected[3],
                        substr_count($attachments[$iterAttachments]->getContent(), $attachmentExpected[2])
                    );
                }
                
                //Test ContentType Attachment
                $this->assertEquals($attachmentExpected[4], $attachments[$iterAttachments]->getContentType());

                //Test ContentDisposition Attachment
                $this->assertEquals($attachmentExpected[5], $attachments[$iterAttachments]->getContentDisposition());

                //Test md5 of Headers Attachment
                $this->assertEquals(
                    $attachmentExpected[6],
                    md5(serialize($attachments[$iterAttachments]->getHeaders()))
                );

                //Remove Attachment
                unlink($attach_dir.$attachments[$iterAttachments]->getFilename());

                $iterAttachments++;

            }
            //Remove Attachment Directory
            rmdir($attach_dir);
        } else {
             $this->assertFalse($Parser->saveAttachments($attach_dir));
        }

        //Test embedded Attachments
        $htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
        $this->assertEquals($countEmbeddedExpected, substr_count($htmlEmbedded, "data:"));
    }
}
