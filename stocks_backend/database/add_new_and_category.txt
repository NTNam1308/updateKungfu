
CREATE TABLE kungfu_news (
    id INT NOT NULL AUTO_INCREMENT,
    mack VARCHAR(20) NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    content LONGTEXT NOT NULL,
    category INT NOT NULL,
    date DATE NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE category_news (
    id INT NOT NULL AUTO_INCREMENT,
    title TEXT NOT NULL,
    PRIMARY KEY (id)
);

INSERT INTO `category_news`(`title`) VALUES ('Tin T?c Th? Gi?i')
INSERT INTO `category_news`(`title`) VALUES ('Tin T?c Trong Nu?c')
INSERT INTO `category_news`(`title`) VALUES ('Chính Tr?')
INSERT INTO `category_news`(`title`) VALUES ('Kinh T?')
INSERT INTO `category_news`(`title`) VALUES ('Th? Thao')
INSERT INTO `category_news`(`title`) VALUES ('Van Hóa')